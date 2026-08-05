<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Phase 1 — Authentication:
|   POST /api/login   Authenticate, returns a Sanctum bearer token + user.
|   GET  /api/user    Current authenticated user (protected).
|   POST /api/logout  Revoke the current token (protected).
|
| Module 2 — Roles & Permissions (all protected; each route also enforces
| a permission via the `permission:` middleware, so direct API calls from
| users without the right role are rejected with 403).
|
*/

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Roles & Permissions
    Route::middleware('permission:roles.view')->group(function () {
        Route::get('/roles', [RoleController::class, 'index']);
        Route::get('/roles/{role}', [RoleController::class, 'show']);
        Route::get('/permissions', [PermissionController::class, 'index']);
    });

    Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:roles.create');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete');
    Route::put('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->middleware('permission:roles.assign_permissions');
});
