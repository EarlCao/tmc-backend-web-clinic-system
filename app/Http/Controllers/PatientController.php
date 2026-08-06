<?php

namespace App\Http\Controllers;

use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PatientController extends Controller
{
    /**
     * List the patient registry.
     */
    public function index(): AnonymousResourceCollection
    {
        return PatientResource::collection(Patient::orderBy('name')->get());
    }

    /**
     * Register a new patient. The form sends camelCase keys; they are mapped
     * onto the table's snake_case columns.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'courseDept' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
            'emergencyContact' => ['nullable', 'string', 'max:255'],
            'allergies' => ['nullable', 'string', 'max:255'],
            'history' => ['nullable', 'string', 'max:255'],
        ]);

        $patient = Patient::create([
            'patient_id' => $validated['id'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'course_dept' => $validated['courseDept'] ?? '',
            'contact' => $validated['contact'] ?? '',
            'emergency_contact' => $validated['emergencyContact'] ?? '',
            'allergies' => $validated['allergies'] ?? 'None',
            'history' => $validated['history'] ?? 'None',
            'status' => 'Active',
        ]);

        return (new PatientResource($patient))->response()->setStatusCode(201);
    }
}
