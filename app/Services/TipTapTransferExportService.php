<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class TipTapTransferExportService
{
    public const FORMAT = 'level-fit-tip-tap-transfer';
    public const VERSION = 1;

    private const REFERENCE_TABLES = [
        'branch_stores',
        'users',
        'method_payments',
        'member_packages',
        'trainer_packages',
        'personal_trainers',
        'trainer_transaction_types',
    ];

    private const CHANGE_TABLES = [
        'members',
        'member_registrations',
        'trainer_sessions',
        'trainers',
        'member_registration_payments',
        'member_registration_installments',
        'check_in_members',
        'leave_days',
        'trainer_session_payments',
        'check_in_trainer_sessions',
        'pt_leave_days',
    ];

    public function create(CarbonInterface $fromExclusive, CarbonInterface $untilInclusive): array
    {
        if ($untilInclusive->lessThanOrEqualTo($fromExclusive)) {
            throw new RuntimeException('Waktu akhir export harus setelah waktu awal.');
        }

        $tables = DB::transaction(function () use ($fromExclusive, $untilInclusive) {
            $result = [];

            foreach (self::REFERENCE_TABLES as $table) {
                $result[$table] = $this->rows(DB::table($table)->orderBy('id')->get());
            }

            foreach (self::CHANGE_TABLES as $table) {
                $result[$table] = $this->rows(
                    DB::table($table)
                        ->where(function ($query) use ($fromExclusive, $untilInclusive) {
                            $query->where(function ($created) use ($fromExclusive, $untilInclusive) {
                                $created->where('created_at', '>', $fromExclusive)
                                    ->where('created_at', '<=', $untilInclusive);
                            })->orWhere(function ($updated) use ($fromExclusive, $untilInclusive) {
                                $updated->where('updated_at', '>', $fromExclusive)
                                    ->where('updated_at', '<=', $untilInclusive);
                            });
                        })
                        ->orderBy('id')
                        ->get()
                );
            }

            return $result;
        }, 3);

        $temporaryPath = tempnam(storage_path('app'), 'tip-tap-transfer-');
        if ($temporaryPath === false) {
            throw new RuntimeException('File sementara export tidak dapat dibuat.');
        }

        $zipPath = $temporaryPath . '.zip';
        @unlink($temporaryPath);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('File ZIP export tidak dapat dibuat.');
        }

        $dataJson = json_encode(['tables' => $tables], JSON_THROW_ON_ERROR);
        $zip->addFromString('data.json', $dataJson);

        [$includedFiles, $missingFiles] = $this->addMemberPhotos($zip, $tables['members']);
        $counts = [];
        foreach ($tables as $table => $rows) {
            $counts[$table] = count($rows);
        }

        $manifest = [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'generated_at' => now()->toIso8601String(),
            'timezone' => config('app.timezone'),
            'from_exclusive' => $fromExclusive->format('Y-m-d H:i:s'),
            'until_inclusive' => $untilInclusive->format('Y-m-d H:i:s'),
            'data_sha256' => hash('sha256', $dataJson),
            'counts' => $counts,
            'files' => $includedFiles,
            'missing_files' => $missingFiles,
        ];
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        if (!$zip->close()) {
            @unlink($zipPath);
            throw new RuntimeException('File ZIP export tidak dapat diselesaikan.');
        }

        return [
            'path' => $zipPath,
            'name' => sprintf(
                'level-fit-tip-tap-%s-sampai-%s.zip',
                $fromExclusive->format('Ymd-His'),
                $untilInclusive->format('Ymd-His')
            ),
            'manifest' => $manifest,
        ];
    }

    private function addMemberPhotos(ZipArchive $zip, array $members): array
    {
        $included = [];
        $missing = [];
        $disk = Storage::disk('public');

        foreach ($members as $member) {
            foreach (['photos', 'small_photos'] as $column) {
                $path = $member[$column] ?? null;
                if (!$this->isSafeAssetPath($path)) {
                    continue;
                }
                if (!$disk->exists($path)) {
                    $missing[] = $path;
                    continue;
                }

                $archivePath = 'files/' . ltrim($path, '/');
                if (!isset($included[$path])) {
                    $zip->addFile($disk->path($path), $archivePath);
                    $included[$path] = $archivePath;
                }
            }
        }

        return [$included, array_values(array_unique($missing))];
    }

    private function isSafeAssetPath($path): bool
    {
        return is_string($path)
            && strpos($path, 'assets/') === 0
            && strpos($path, '..') === false;
    }

    private function rows($collection): array
    {
        return $collection->map(function ($row) {
            return (array) $row;
        })->all();
    }
}
