<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class IdempotentSubmission
{
    private const CACHE_MINUTES = 30;

    public static function claim(string $token, string $scope, int $userId): ?string
    {
        $cacheKey = self::cacheKey($token, $scope, $userId);

        if (!Cache::add($cacheKey, 'processing', now()->addMinutes(self::CACHE_MINUTES))) {
            return null;
        }

        return $cacheKey;
    }

    public static function complete(string $cacheKey): void
    {
        Cache::put($cacheKey, 'completed', now()->addMinutes(self::CACHE_MINUTES));
    }

    public static function release(?string $cacheKey): void
    {
        if ($cacheKey) {
            Cache::forget($cacheKey);
        }
    }

    private static function cacheKey(string $token, string $scope, int $userId): string
    {
        return 'idempotent-submission:' . hash('sha256', $userId . '|' . $scope . '|' . $token);
    }
}
