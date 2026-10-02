<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use ZipArchive;

class TipTapTransferImportService
{
    private const FORMAT = 'level-fit-tip-tap-transfer';
    private const VERSION = 1;
    private const MAX_UNCOMPRESSED_BYTES = 209715200;

    private const REFERENCE_TABLES = [
        'branch_stores',
        'users',
        'method_payments',
        'member_packages',
        'trainer_packages',
        'personal_trainers',
        'trainer_transaction_types',
    ];

    private const ROOT_TABLES = [
        'members' => ['kind' => 'member'],
        'member_registrations' => ['kind' => 'membership', 'parent' => ['members', 'member_id']],
        'trainer_sessions' => ['kind' => 'pt', 'parent' => ['members', 'member_id']],
    ];

    private const CHILD_TABLES = [
        'trainers' => [['member_registrations', 'member_id']],
        'member_registration_payments' => [['member_registrations', 'member_registration_id']],
        'member_registration_installments' => [['member_registrations', 'member_registration_id']],
        'check_in_members' => [['member_registrations', 'member_registration_id']],
        'leave_days' => [['member_registrations', 'member_registration_id']],
        'trainer_session_payments' => [['trainer_sessions', 'trainer_session_id']],
        'check_in_trainer_sessions' => [['trainer_sessions', 'trainer_session_id']],
        'pt_leave_days' => [
            ['trainer_sessions', 'trainer_session_id'],
            ['leave_days', 'member_leave_day_id', true],
        ],
    ];

    private $columns = [];

    public function import(UploadedFile $file): array
    {
        $zip = new ZipArchive();
        if ($zip->open($file->getRealPath()) !== true) {
            throw ValidationException::withMessages(['transfer_file' => 'File transfer bukan ZIP yang valid.']);
        }

        try {
            $this->guardArchiveSize($zip);
            $manifestJson = $zip->getFromName('manifest.json');
            $dataJson = $zip->getFromName('data.json');
            if ($manifestJson === false || $dataJson === false) {
                throw ValidationException::withMessages(['transfer_file' => 'Manifest atau data transfer tidak ditemukan.']);
            }

            $manifest = json_decode($manifestJson, true, 512, JSON_THROW_ON_ERROR);
            $data = json_decode($dataJson, true, 512, JSON_THROW_ON_ERROR);
            $this->validateEnvelope($manifest, $data, $dataJson);
            $this->validateFiles($zip, $manifest['files'] ?? []);
            $fromExclusive = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $manifest['from_exclusive'],
                $manifest['timezone'] ?? config('app.timezone')
            );

            $summary = DB::transaction(function () use ($data, $fromExclusive) {
                DB::table('locks')->where('id', 1)->lockForUpdate()->first();
                $summary = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'references' => 0];

                foreach (self::REFERENCE_TABLES as $table) {
                    foreach ($data['tables'][$table] ?? [] as $row) {
                        $row = $this->compatibleRow($table, $row);
                        if (!$this->exists($table, $row['id'])) {
                            DB::table($table)->insert($row);
                            $summary['references']++;
                        }
                    }
                }

                foreach (self::ROOT_TABLES as $table => $rules) {
                    foreach ($data['tables'][$table] ?? [] as $row) {
                        $this->importRoot($table, $rules, $row, $fromExclusive, $summary);
                    }
                }

                foreach (self::CHILD_TABLES as $table => $parents) {
                    foreach ($data['tables'][$table] ?? [] as $row) {
                        $this->importChild($table, $parents, $row, $summary);
                    }
                }

                return $summary;
            }, 3);

            $summary['files'] = $this->importFiles($zip, $manifest['files'] ?? []);
            $summary['source_missing_files'] = count($manifest['missing_files'] ?? []);
            $summary['from_exclusive'] = $manifest['from_exclusive'];
            $summary['until_inclusive'] = $manifest['until_inclusive'];

            return $summary;
        } catch (\JsonException $exception) {
            throw ValidationException::withMessages(['transfer_file' => 'Isi JSON pada file transfer rusak.']);
        } finally {
            $zip->close();
        }
    }

    private function importRoot(string $table, array $rules, array $row, Carbon $fromExclusive, array &$summary): void
    {
        $row = $this->compatibleRow($table, $row);
        $id = $row['id'];

        if ($this->exists($table, $id)) {
            $this->update($table, $id, $row);
            $summary['updated']++;
            return;
        }

        if ($this->isArchived($rules['kind'], $id)
            || !$this->wasCreatedAfter($row, $fromExclusive)
            || (isset($rules['parent']) && !$this->parentExists($rules['parent'], $row))) {
            $summary['skipped']++;
            return;
        }

        DB::table($table)->insert($row);
        $summary['inserted']++;
    }

    private function importChild(string $table, array $parents, array $row, array &$summary): void
    {
        $row = $this->compatibleRow($table, $row);
        $id = $row['id'];

        foreach ($parents as $parent) {
            $nullable = $parent[2] ?? false;
            if ($nullable && empty($row[$parent[1]])) {
                continue;
            }
            if (!$this->parentExists($parent, $row)) {
                $summary['skipped']++;
                return;
            }
        }

        if ($this->exists($table, $id)) {
            $this->update($table, $id, $row);
            $summary['updated']++;
            return;
        }

        DB::table($table)->insert($row);
        $summary['inserted']++;
    }

    private function validateEnvelope(array $manifest, array $data, string $dataJson): void
    {
        if (($manifest['format'] ?? null) !== self::FORMAT || (int) ($manifest['version'] ?? 0) !== self::VERSION) {
            throw ValidationException::withMessages(['transfer_file' => 'Format atau versi file transfer tidak didukung.']);
        }
        if (!isset($manifest['from_exclusive'], $manifest['until_inclusive'], $manifest['data_sha256'])) {
            throw ValidationException::withMessages(['transfer_file' => 'Metadata waktu atau checksum tidak lengkap.']);
        }
        if (!hash_equals((string) $manifest['data_sha256'], hash('sha256', $dataJson))) {
            throw ValidationException::withMessages(['transfer_file' => 'Checksum file transfer tidak cocok. File mungkin rusak.']);
        }
        if (!isset($data['tables']) || !is_array($data['tables'])) {
            throw ValidationException::withMessages(['transfer_file' => 'Daftar tabel pada file transfer tidak valid.']);
        }

        $allowed = array_merge(self::REFERENCE_TABLES, array_keys(self::ROOT_TABLES), array_keys(self::CHILD_TABLES));
        foreach ($data['tables'] as $table => $rows) {
            if (!in_array($table, $allowed, true) || !is_array($rows)) {
                throw ValidationException::withMessages(['transfer_file' => 'File transfer berisi tabel yang tidak diizinkan.']);
            }
        }
    }

    private function guardArchiveSize(ZipArchive $zip): void
    {
        if ($zip->numFiles > 1000) {
            throw ValidationException::withMessages(['transfer_file' => 'File transfer berisi terlalu banyak file.']);
        }

        $total = 0;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            $total += (int) ($stat['size'] ?? 0);
            if ($total > self::MAX_UNCOMPRESSED_BYTES) {
                throw ValidationException::withMessages(['transfer_file' => 'Ukuran isi file transfer melebihi 200 MB.']);
            }
        }
    }

    private function compatibleRow(string $table, array $row): array
    {
        if (!Schema::hasTable($table)) {
            throw new RuntimeException("Tabel {$table} belum tersedia di database Tip-Tap.");
        }
        if (!isset($this->columns[$table])) {
            $this->columns[$table] = array_flip(Schema::getColumnListing($table));
        }
        $row = array_intersect_key($row, $this->columns[$table]);
        if (!isset($row['id'])) {
            throw ValidationException::withMessages(['transfer_file' => "ID pada tabel {$table} tidak lengkap."]);
        }

        return $row;
    }

    private function update(string $table, $id, array $row): void
    {
        unset($row['id']);
        DB::table($table)->where('id', $id)->update($row);
    }

    private function parentExists(array $parent, array $row): bool
    {
        return isset($row[$parent[1]]) && $this->exists($parent[0], $row[$parent[1]]);
    }

    private function exists(string $table, $id): bool
    {
        return DB::table($table)->where('id', $id)->exists();
    }

    private function isArchived(string $kind, $id): bool
    {
        return DB::table('trashes')->where('kind', $kind)->where('original_id', $id)->exists();
    }

    private function wasCreatedAfter(array $row, Carbon $fromExclusive): bool
    {
        return !empty($row['created_at']) && Carbon::parse($row['created_at'])->greaterThan($fromExclusive);
    }

    private function importFiles(ZipArchive $zip, array $files): int
    {
        $stored = 0;
        $disk = Storage::disk('public');
        foreach ($files as $path => $archivePath) {
            if (!$this->isSafeAssetPath($path)
                || !is_string($archivePath)
                || strpos($archivePath, 'files/assets/') !== 0
                || strpos($archivePath, '..') !== false) {
                throw ValidationException::withMessages(['transfer_file' => 'Path file aset pada transfer tidak aman.']);
            }

            $stream = $zip->getStream($archivePath);
            if ($stream === false) {
                throw ValidationException::withMessages(['transfer_file' => "File aset {$archivePath} tidak ditemukan."]);
            }
            try {
                if (!$disk->put($path, $stream)) {
                    throw new RuntimeException("File aset {$path} tidak dapat disimpan.");
                }
            } finally {
                fclose($stream);
            }
            $stored++;
        }

        return $stored;
    }

    private function validateFiles(ZipArchive $zip, array $files): void
    {
        foreach ($files as $path => $archivePath) {
            if (!$this->isSafeAssetPath($path)
                || !is_string($archivePath)
                || strpos($archivePath, 'files/assets/') !== 0
                || strpos($archivePath, '..') !== false
                || $zip->locateName($archivePath) === false) {
                throw ValidationException::withMessages([
                    'transfer_file' => 'Daftar file aset pada transfer tidak valid atau tidak lengkap.',
                ]);
            }
        }
    }

    private function isSafeAssetPath($path): bool
    {
        return is_string($path)
            && strpos($path, 'assets/') === 0
            && strpos($path, '..') === false;
    }
}
