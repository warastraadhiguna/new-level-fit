<?php
namespace App\Services;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
class FaceEnrollmentService
{
    public function enroll(string $image): array
    {
        try {
            $response = Http::acceptJson()->withOptions(['connect_timeout' => 3])->timeout(config('face.timeout'))
                ->attach('image', $image, 'capture.jpg')
                ->post(rtrim(config('face.url'), '/').'/face/enroll');
        } catch (ConnectionException $e) {
            throw ValidationException::withMessages(['photo' => 'Service wajah sedang tidak tersedia. Silakan coba kembali.']);
        }
        $data = $response->json();
        if (!$response->successful() || !is_array($data) || ($data['success'] ?? false) !== true) {
            throw ValidationException::withMessages(['photo' => is_string($data['message'] ?? null) ? $data['message'] : 'Service wajah gagal memproses foto.']);
        }
        $embedding = $data['embedding'] ?? null;
        $valid = ($data['face_count'] ?? null) === 1 && ($data['model'] ?? null) === config('face.model')
            && is_array($embedding) && count($embedding) === 128 && array_keys($embedding) === range(0, 127);
        $norm = 0;
        foreach (is_array($embedding) ? $embedding : [] as $value) {
            if (!is_numeric($value) || !is_finite((float) $value)) { $valid = false; break; }
            $norm += $value * $value;
        }
        if (!$valid || !is_finite($norm) || $norm <= 0) {
            throw ValidationException::withMessages(['photo' => 'Respons model wajah tidak valid.']);
        }
        return $data;
    }
}
