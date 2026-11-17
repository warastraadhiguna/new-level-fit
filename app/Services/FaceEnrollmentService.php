<?php
namespace App\Services;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
class FaceEnrollmentService
{
    public function enroll(string $image): array
    {
        try {
            $response = Http::acceptJson()->withOptions(['connect_timeout' => 3])->timeout(config('face.timeout'))
                ->attach('image', $image, 'capture.jpg')
                ->post(rtrim(config('face.url'), '/').'/face/enroll',['check_quality'=>'true']);
        } catch (ConnectionException $e) {
            report($e);
            throw ValidationException::withMessages(['photo' => 'Pendaftaran wajah belum dapat diproses. Silakan coba kembali.']);
        }
        $data = $response->json();
        if (!$response->successful() || !is_array($data) || ($data['success'] ?? false) !== true) {
            $code=is_string($data['code'] ?? null) ? $data['code'] : '';
            $messages=[
                'no_face'=>'Wajah belum terlihat. Posisikan wajah di depan kamera.',
                'multiple_faces'=>'Pastikan hanya satu wajah berada dalam foto.',
                'face_too_small'=>'Dekatkan wajah ke kamera dan ambil foto ulang.',
                'face_clipped'=>'Pastikan seluruh wajah masuk bingkai kamera.',
                'face_too_dark'=>'Gunakan pencahayaan dari depan lalu ambil foto ulang.',
                'face_too_bright'=>'Kurangi cahaya langsung lalu ambil foto ulang.',
                'face_blurry'=>'Foto buram. Tahan kamera agar stabil dan ambil foto ulang.',
            ];
            Log::warning('Face enrollment processing failed',['status'=>$response->status(),'code'=>$code,'detail'=>$data['message'] ?? null]);
            throw ValidationException::withMessages(['photo' => $response->status()<500 ? ($messages[$code] ?? 'Foto belum dapat diproses. Ambil foto ulang atau hubungi pengelola.') : 'Pendaftaran wajah belum dapat diproses. Silakan coba kembali.']);
        }
        if (($data['quality_checked'] ?? false) !== true) {
            Log::warning('Face enrollment response missing quality verification');
            throw ValidationException::withMessages(['photo'=>'Pendaftaran wajah belum dapat diproses. Silakan hubungi pengelola.']);
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
            Log::warning('Invalid face enrollment response');
            throw ValidationException::withMessages(['photo' => 'Foto belum dapat diproses. Ambil foto ulang atau hubungi pengelola.']);
        }
        return $data;
    }
}
