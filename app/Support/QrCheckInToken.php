<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use RuntimeException;

class QrCheckInToken
{
    private const MEMBER_PREFIX = 'LEVELFIT_CHECKIN:';
    private const TRAINER_SESSION_PREFIX = 'LEVELFIT_TRAINER_SESSION:';

    public static function issue(int $branchStoreId, int $userId, string $purpose = 'member'): array
    {
        $prefix = self::prefixFor($purpose);
        $now = Carbon::now('Asia/Jakarta');
        $expiresAt = $now->copy()->addSeconds((int) config('services.qr_check_in.ttl', 120));
        $payload = self::base64UrlEncode(json_encode([
            'branch_store_id' => $branchStoreId,
            'user_id' => $userId,
            'purpose' => $purpose,
            'issued_at' => $now->timestamp,
            'expires_at' => $expiresAt->timestamp,
            'nonce' => bin2hex(random_bytes(12)),
        ]));

        return [
            'value' => $prefix . $payload . '.' . self::signature($payload),
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    public static function parse(string $token, string $expectedPurpose = 'member'): array
    {
        $token = trim($token);
        $prefix = self::prefixFor($expectedPurpose);

        if (strpos($token, $prefix) !== 0) {
            throw new RuntimeException('Jenis QR code tidak sesuai.');
        }

        $token = substr($token, strlen($prefix));

        $parts = explode('.', $token, 2);
        if (count($parts) !== 2 || !hash_equals(self::signature($parts[0]), $parts[1])) {
            throw new RuntimeException('QR code tidak valid.');
        }

        $payload = json_decode(self::base64UrlDecode($parts[0]), true);
        if (!is_array($payload)
            || empty($payload['branch_store_id'])
            || empty($payload['user_id'])
            || empty($payload['expires_at'])
            || ($payload['purpose'] ?? 'member') !== $expectedPurpose) {
            throw new RuntimeException('Isi QR code tidak valid.');
        }

        if ((int) $payload['expires_at'] < Carbon::now('Asia/Jakarta')->timestamp) {
            throw new RuntimeException('QR code sudah kedaluwarsa. Silakan scan QR terbaru di resepsionis.');
        }

        return $payload;
    }

    private static function prefixFor(string $purpose): string
    {
        if ($purpose === 'member') {
            return self::MEMBER_PREFIX;
        }

        if ($purpose === 'trainer_session') {
            return self::TRAINER_SESSION_PREFIX;
        }

        throw new RuntimeException('Jenis QR check-in tidak didukung.');
    }

    private static function signature(string $payload): string
    {
        $secret = (string) config('services.qr_check_in.secret');
        if ($secret === '') {
            throw new RuntimeException('QR check-in belum dikonfigurasi.');
        }

        return hash_hmac('sha256', $payload, $secret);
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;
        if ($padding) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if ($decoded === false) {
            throw new RuntimeException('Isi QR code tidak valid.');
        }

        return $decoded;
    }
}
