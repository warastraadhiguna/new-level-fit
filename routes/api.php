<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\GateMemberController;
use App\Http\Controllers\Trainer\TrainerSessionCheckInController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->prefix('gate')->group(function () {
    Route::get('/members', [GateMemberController::class, 'index']);
    Route::post('/check-in', [GateMemberController::class, 'checkIn']);
    Route::post('/check-out', [GateMemberController::class, 'checkOut']);
});

Route::post('/member-qr-check-in/toggle', [GateMemberController::class, 'toggleQr'])
    ->withoutMiddleware('throttle:api')
    ->middleware('throttle:qr-check-in');

Route::post('/trainer-session-qr-check-in/toggle', [TrainerSessionCheckInController::class, 'toggleQr'])
    ->withoutMiddleware('throttle:api')
    ->middleware('throttle:qr-check-in');
