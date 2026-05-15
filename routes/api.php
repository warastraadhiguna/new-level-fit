<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\GateMemberController;
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
