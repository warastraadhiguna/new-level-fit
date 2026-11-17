<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        // Validation and authentication retain their normal field errors/redirects.
        if ($exception instanceof \Illuminate\Validation\ValidationException
            || $exception instanceof \Illuminate\Auth\AuthenticationException
            || $exception instanceof \Illuminate\Http\Exceptions\HttpResponseException) {
            return parent::render($request,$exception);
        }

        $exception=$this->prepareException($exception);
        $http=$exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
        $status=$http ? $exception->getStatusCode() : 500;
        $headers=$http ? $exception->getHeaders() : [];
        $message=$status>=500
            ? 'Layanan belum dapat digunakan. Silakan coba kembali atau hubungi pengelola.'
            : ($status===404 ? 'Halaman tidak ditemukan.' : ($exception->getMessage() ?: 'Permintaan tidak dapat diproses.'));

        // Never expose traces, queries, model names or connection details, even if
        // a deployment accidentally leaves APP_DEBUG enabled. Reporting is unchanged.
        $response=$request->expectsJson()
            ? response()->json(['message'=>$message],$status,$headers)
            : response()->view('errors.safe',['status'=>$status,'message'=>$message],$status,$headers);
        return $response->header('Cache-Control','no-store, private');
    }
}
