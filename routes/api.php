<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClinicEventController;
use App\Http\Controllers\ClinicInsightsController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StaffController;
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

    // Appointments (Module 3)
    Route::middleware('permission:appointments.view')->group(function () {
        Route::get('/appointments', [AppointmentController::class, 'index']);
        Route::get('/appointments/{appointment}', [AppointmentController::class, 'show']);
    });

    Route::post('/appointments', [AppointmentController::class, 'store'])->middleware('permission:appointments.create');
    // The target status determines the required permission (approve/reject/update),
    // so the check happens inside the controller rather than a static middleware.
    Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);
    Route::post('/appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->middleware('permission:appointments.reschedule');

    // Staff roster (dashboard duty schedule)
    Route::get('/staff', [StaffController::class, 'index'])->middleware('permission:schedules.view');
    Route::patch('/staff/status', [StaffController::class, 'updateStatus'])->middleware('permission:schedules.update');

    // Patients registry
    Route::get('/patients', [PatientController::class, 'index'])->middleware('permission:patients.view');
    Route::post('/patients', [PatientController::class, 'store'])->middleware('permission:patients.create');

    // Consultations
    Route::middleware('permission:consultations.view')->group(function () {
        Route::get('/consultations', [ConsultationController::class, 'index']);
        Route::get('/consultations/{consultation}', [ConsultationController::class, 'show']);
    });

    Route::post('/consultations', [ConsultationController::class, 'store'])->middleware('permission:consultations.create');
    Route::post('/consultations/{consultation}/start', [ConsultationController::class, 'start'])->middleware('permission:consultations.create');
    Route::patch('/consultations/{consultation}', [ConsultationController::class, 'update'])->middleware('permission:consultations.update');
    Route::post('/consultations/{consultation}/complete', [ConsultationController::class, 'complete'])->middleware('permission:consultations.update');

    // Medical records (+ nested conditions/allergies)
    Route::get('/medical-records', [MedicalRecordController::class, 'index'])->middleware('permission:medical_records.view');
    Route::middleware('permission:medical_records.update')->group(function () {
        Route::post('/medical-records/{record}/conditions', [MedicalRecordController::class, 'storeCondition']);
        Route::patch('/medical-records/{record}/conditions/{condition}', [MedicalRecordController::class, 'updateCondition']);
        Route::delete('/medical-records/{record}/conditions/{condition}', [MedicalRecordController::class, 'destroyCondition']);
        Route::post('/medical-records/{record}/allergies', [MedicalRecordController::class, 'storeAllergy']);
        Route::patch('/medical-records/{record}/allergies/{allergy}', [MedicalRecordController::class, 'updateAllergy']);
        Route::delete('/medical-records/{record}/allergies/{allergy}', [MedicalRecordController::class, 'destroyAllergy']);
    });

    // Campus health events
    Route::get('/events', [ClinicEventController::class, 'index'])->middleware('permission:calendar.view');
    Route::post('/events', [ClinicEventController::class, 'store'])->middleware('permission:calendar.create');

    // Dashboard insights (activity bars + peak hours)
    Route::get('/insights/activity', [ClinicInsightsController::class, 'activity'])->middleware('permission:dashboard.view');
    Route::get('/insights/peak-hours', [ClinicInsightsController::class, 'peakHours'])->middleware('permission:dashboard.view');

    // Activity / audit log
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->middleware('permission:audit_logs.view');
    // Any authenticated user may record their own actions in the log.
    Route::post('/activity-logs', [ActivityLogController::class, 'store']);
});
