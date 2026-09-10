<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PreventDuplicateFormSubmission
{
    private const CACHE_MINUTES = 30;

    public function handle(Request $request, Closure $next)
    {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
            return $next($request);
        }

        $token = (string) $request->input('_form_submission_token', '');
        if ($token === '' || strlen($token) > 100) {
            return $next($request);
        }

        $actor = $request->user()
            ? 'user:' . $request->user()->getAuthIdentifier()
            : 'session:' . $request->session()->getId();
        $route = $request->route();
        $scope = $route && $route->getName()
            ? $route->getName()
            : $request->path();
        $cacheKey = 'form-submission:' . hash('sha256', implode('|', [
            $actor,
            $request->method(),
            $scope,
            $token,
        ]));

        if (!Cache::add($cacheKey, 'processing', now()->addMinutes(self::CACHE_MINUTES))) {
            $message = 'Form ini sedang atau sudah diproses. Jangan klik tombol simpan berulang kali.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 409);
            }

            return redirect()->back()->with('errorr', $message);
        }

        try {
            $response = $next($request);

            if ($response->getStatusCode() >= 400) {
                Cache::forget($cacheKey);
            } else {
                Cache::put($cacheKey, 'completed', now()->addMinutes(self::CACHE_MINUTES));
            }

            return $response;
        } catch (\Throwable $exception) {
            Cache::forget($cacheKey);
            throw $exception;
        }
    }
}
