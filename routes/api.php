<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Client\ClientAuthController;
use App\Http\Controllers\Client\FaceVerificationController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

Route::post('/login',  [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user',      [AuthController::class, 'me']);
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Clients — all authenticated users can read and create
    Route::get('/clients',                       [ClientController::class, 'index']);
    Route::post('/clients',                      [ClientController::class, 'store']);
    Route::get('/clients/{client}',              [ClientController::class, 'show']);
    Route::patch('/clients/{client}/photo',      [ClientController::class, 'updatePhoto']);

    // Admin-only client actions
    Route::put('/clients/{client}',    [ClientController::class, 'update'])->middleware('role:admin');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->middleware('role:admin');

    // Users — admin only
    Route::middleware('role:admin')->group(function () {
        Route::get('/users',          [UserController::class, 'index']);
        Route::post('/users',         [UserController::class, 'store']);
        Route::get('/users/{user}',   [UserController::class, 'show']);
        Route::put('/users/{user}',   [UserController::class, 'update']);
        Route::delete('/users/{user}',[UserController::class, 'destroy']);

        // Admin: face verification log
        Route::get('/verifications', [VerificationController::class, 'index']);
    });
});

// Client app — Bearer token only (no CSRF, no session cookies)
Route::prefix('client')->group(function () {
    Route::post('/login', [ClientAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [ClientAuthController::class, 'logout']);
        Route::get('/profile', [ClientAuthController::class, 'profile']);
        Route::post('/verify', [FaceVerificationController::class, 'verify']);
    });
});
