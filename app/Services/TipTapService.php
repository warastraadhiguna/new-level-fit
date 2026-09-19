<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Archive complete rows, rather than relying on Eloquent scopes: legacy SQL,
 * exports, gate access and revenue all read the operational tables directly.
 * The order below is parent-first; removal always runs in reverse order.
 */
class TipTapService
{
    private const ROOTS = [
        'member' => 'members',
        'membership' => 'member_registrations',
        'pt' => 'trainer_sessions',
    ];

    private const RELATIONS = [
        'members' => [],
        'member_registrations' => ['member_id' => 'members'],
        'trainer_sessions' => ['member_id' => 'members'],
        'class_details' => ['member_id' => 'members'],
        // Legacy table: its FK points to registrations, despite the column name.
        'trainers' => ['member_id' => 'member_registrations'],
        'member_registration_payments' => ['member_registration_id' => 'member_registrations'],
        'member_registration_installments' => ['member_registration_id' => 'member_registrations'],
        'check_in_members' => ['member_registration_id' => 'member_registrations'],
        'leave_days' => ['member_registration_id' => 'member_registrations'],
        'trainer_session_payments' => ['trainer_session_id' => 'trainer_sessions'],
        'check_in_trainer_sessions' => ['trainer_session_id' => 'trainer_sessions'],
        'pt_leave_days' => ['trainer_session_id' => 'trainer_sessions', 'member_leave_day_id' => 'leave_days'],
    ];

    public function trash(User $actor, string $kind, int $id, ?int $memberId = null): void
    {
        $this->authorize($actor);
        abort_unless(isset(self::ROOTS[$kind]), 404);

        DB::transaction(function () use ($kind, $id, $memberId) {
            $this->lock();
            $table = self::ROOTS[$kind];
            $root = DB::table($table)->where('id', $id)->lockForUpdate()->first();
            abort_unless($root, 404);
            $ownerId = $kind === 'member' ? $id : (int) $root->member_id;
            abort_if($memberId !== null && $memberId !== $ownerId, 404);
            $member = DB::table('members')->where('id', $ownerId)->lockForUpdate()->first();
            abort_unless($member, 404);

            $rows = [$table => [(array) $root]];
            foreach (self::RELATIONS as $child => $relations) {
                if ($child === $table || !Schema::hasTable($child)) {
                    continue;
                }
                $filters = [];
                foreach ($relations as $column => $parent) {
                    if (!empty($rows[$parent]) && Schema::hasColumn($child, $column)) {
                        $filters[$column] = array_column($rows[$parent], 'id');
                    }
                }
                if (!$filters) {
                    continue;
                }
                $rows[$child] = DB::table($child)->where(function ($query) use ($filters) {
                    foreach ($filters as $column => $ids) {
                        $query->orWhereIn($column, $ids);
                    }
                })->orderBy('id')->lockForUpdate()->get()->map(function ($row) {
                    return (array) $row;
                })->all();
            }

            DB::table('trashes')->insert([
                'kind' => $kind,
                'original_id' => $id,
                'member_id' => $ownerId,
                'label' => $member->full_name . ' — ' . $kind . ' #' . $id,
                'member_code' => $kind === 'member' ? ($member->member_code ?? null) : null,
                'card_number' => $kind === 'member' ? ($member->card_number ?? null) : null,
                'payload' => $this->encode($rows),
                'deleted_at' => now(),
            ]);

            foreach (array_reverse(array_keys(self::RELATIONS)) as $child) {
                if (!empty($rows[$child])) {
                    DB::table($child)->whereIn('id', array_column($rows[$child], 'id'))->delete();
                }
            }
        }, 3);
    }

    public function restore(User $actor, int $id): void
    {
        $this->authorize($actor);
        DB::transaction(function () use ($id) {
            $this->lock();
            $entry = $this->entry($id);
            if ($entry->kind !== 'member' && !DB::table('members')->where('id', $entry->member_id)->exists()) {
                throw ValidationException::withMessages(['trash' => 'Restore member terlebih dahulu.']);
            }
            $rows = $this->decode($entry->payload);
            foreach (self::RELATIONS as $table => $relations) {
                foreach ($rows[$table] ?? [] as $row) {
                    // Never overwrite a new record or silently drop a conflicting row.
                    DB::table($table)->insert($row);
                }
            }
            DB::table('trashes')->where('id', $id)->delete();
        }, 3);
    }

    public function purge(User $actor, int $id): void
    {
        $this->authorize($actor);
        DB::transaction(function () use ($id) {
            $this->lock();
            $entry = $this->entry($id);
            $entries = DB::table('trashes')->where('member_id', $entry->member_id)->lockForUpdate()->get();
            $removed = [];
            foreach ($entries as $candidate) {
                if ($entry->kind !== 'member' && $candidate->id !== $entry->id) {
                    continue;
                }
                $rows = $this->decode($candidate->payload);
                foreach ($rows as $table => $records) {
                    $removed[$table] = array_merge($removed[$table] ?? [], array_column($records, 'id'));
                    foreach ($records as $record) {
                        foreach (['photos', 'small_photos'] as $column) {
                            $path = $record[$column] ?? null;
                            if ($path && strpos($path, 'assets/') === 0 && strpos($path, '..') === false) {
                                $disk = Storage::disk('public');
                                if ($disk->exists($path) && !$disk->delete($path)) {
                                    throw ValidationException::withMessages(['trash' => 'File foto belum dapat dihapus. Coba kembali.']);
                                }
                            }
                        }
                    }
                }
                DB::table('trashes')->where('id', $candidate->id)->delete();
            }

            // A linked PT freeze can be in a separately trashed registration.
            // Permanent deletion must not leave an unrestorable reference there.
            foreach (DB::table('trashes')->where('member_id', $entry->member_id)->get() as $remaining) {
                $rows = $this->decode($remaining->payload);
                foreach (self::RELATIONS as $table => $relations) {
                    $rows[$table] = array_values(array_filter($rows[$table] ?? [], function ($row) use ($relations, &$removed, $table) {
                        foreach ($relations as $column => $parent) {
                            if (in_array($row[$column] ?? null, $removed[$parent] ?? [], true)) {
                                $removed[$table][] = $row['id'];
                                return false;
                            }
                        }
                        return true;
                    }));
                }
                DB::table('trashes')->where('id', $remaining->id)->update(['payload' => $this->encode($rows)]);
            }
        }, 3);
    }

    private function authorize(User $actor): void
    {
        abort_unless($actor->isOwner(), 403, 'Tip-Tap hanya untuk Owner.');
    }

    private function lock(): void
    {
        // Serialize overlapping member/registration operations across requests.
        DB::table('locks')->where('id', 1)->lockForUpdate()->first();
    }

    private function entry(int $id)
    {
        $entry = DB::table('trashes')->where('id', $id)->lockForUpdate()->first();
        abort_unless($entry, 404);
        return $entry;
    }

    private function encode(array $rows): string
    {
        return Crypt::encryptString(json_encode($rows, JSON_THROW_ON_ERROR));
    }

    private function decode(string $payload): array
    {
        return json_decode(Crypt::decryptString($payload), true, 512, JSON_THROW_ON_ERROR);
    }
}
