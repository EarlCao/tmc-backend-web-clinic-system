<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Phase 1 — Authentication & Authorization.
|
| POST /api/login    Authenticate with email/password, returns a Sanctum
|                    bearer token plus the authenticated user.
| GET  /api/user     Return the currently authenticated user (protected).
| POST /api/logout   Revoke the current token (protected).
|
*/

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
