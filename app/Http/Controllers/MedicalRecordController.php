<?php

namespace App\Http\Controllers;

use App\Http\Resources\MedicalRecordResource;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordAllergy;
use App\Models\MedicalRecordCondition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MedicalRecordController extends Controller
{
    /**
     * List medical records with their clinical child sections loaded.
     */
    public function index(): AnonymousResourceCollection
    {
        return MedicalRecordResource::collection(
            MedicalRecord::with(['histories', 'conditions', 'allergies', 'medications'])
                ->orderBy('name')
                ->get(),
        );
    }

    // ---------- Medical conditions ----------

    /**
     * Add a diagnosed condition to a record; returns the full updated record.
     */
    public function storeCondition(Request $request, MedicalRecord $record): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'diagnosedDate' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $record->conditions()->create([
            'name' => $validated['name'],
            'status' => $validated['status'] ?? 'Active',
            'diagnosed_date' => $validated['diagnosedDate'] ?? now()->toDateString(),
            'notes' => $validated['notes'] ?? '',
        ]);
        $record->update(['last_updated' => now()->toDateString()]);

        return $this->resourceWithChildren($record)->response();
    }

    /**
     * Update a condition on a record; returns the full updated record.
     */
    public function updateCondition(
        Request $request,
        MedicalRecord $record,
        MedicalRecordCondition $condition,
    ): JsonResponse {
        if ($condition->medical_record_id !== $record->id) {
            abort(404, 'Condition not found on this record.');
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'diagnosedDate' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $condition->update([
            'name' => $validated['name'] ?? $condition->name,
            'status' => $validated['status'] ?? $condition->status,
            'diagnosed_date' => $validated['diagnosedDate'] ?? $condition->diagnosed_date?->format('Y-m-d'),
            'notes' => $validated['notes'] ?? $condition->notes,
        ]);
        $record->update(['last_updated' => now()->toDateString()]);

        return $this->resourceWithChildren($record)->response();
    }

    /**
     * Remove a condition from a record; returns the full updated record.
     */
    public function destroyCondition(
        MedicalRecord $record,
        MedicalRecordCondition $condition,
    ): JsonResponse {
        if ($condition->medical_record_id !== $record->id) {
            abort(404, 'Condition not found on this record.');
        }

        $condition->delete();
        $record->update(['last_updated' => now()->toDateString()]);

        return $this->resourceWithChildren($record)->response();
    }

    // ---------- Allergies ----------

    /**
     * Record an allergy on a record; returns the full updated record.
     */
    public function storeAllergy(Request $request, MedicalRecord $record): JsonResponse
    {
        $validated = $request->validate([
            'allergen' => ['required', 'string', 'max:255'],
            'reaction' => ['nullable', 'string', 'max:255'],
            'severity' => ['nullable', 'string', 'max:255'],
            'dateRecorded' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $record->allergies()->create([
            'allergen' => $validated['allergen'],
            'reaction' => $validated['reaction'] ?? '',
            'severity' => $validated['severity'] ?? 'Moderate',
            'date_recorded' => $validated['dateRecorded'] ?? now()->toDateString(),
            'notes' => $validated['notes'] ?? '',
        ]);
        $record->update(['last_updated' => now()->toDateString()]);

        return $this->resourceWithChildren($record)->response();
    }

    /**
     * Update an allergy on a record; returns the full updated record.
     */
    public function updateAllergy(
        Request $request,
        MedicalRecord $record,
        MedicalRecordAllergy $allergy,
    ): JsonResponse {
        if ($allergy->medical_record_id !== $record->id) {
            abort(404, 'Allergy not found on this record.');
        }

        $validated = $request->validate([
            'allergen' => ['nullable', 'string', 'max:255'],
            'reaction' => ['nullable', 'string', 'max:255'],
            'severity' => ['nullable', 'string', 'max:255'],
            'dateRecorded' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $allergy->update([
            'allergen' => $validated['allergen'] ?? $allergy->allergen,
            'reaction' => $validated['reaction'] ?? $allergy->reaction,
            'severity' => $validated['severity'] ?? $allergy->severity,
            'date_recorded' => $validated['dateRecorded'] ?? $allergy->date_recorded?->format('Y-m-d'),
            'notes' => $validated['notes'] ?? $allergy->notes,
        ]);
        $record->update(['last_updated' => now()->toDateString()]);

        return $this->resourceWithChildren($record)->response();
    }

    /**
     * Remove an allergy from a record; returns the full updated record.
     */
    public function destroyAllergy(
        MedicalRecord $record,
        MedicalRecordAllergy $allergy,
    ): JsonResponse {
        if ($allergy->medical_record_id !== $record->id) {
            abort(404, 'Allergy not found on this record.');
        }

        $allergy->delete();
        $record->update(['last_updated' => now()->toDateString()]);

        return $this->resourceWithChildren($record)->response();
    }

    /**
     * Reload the record with children and return its resource instance.
     *
     * The resource response wraps the record in { data: ... }, matching what
     * the frontend services expect from every other mutation endpoint.
     */
    private function resourceWithChildren(MedicalRecord $record): MedicalRecordResource
    {
        return new MedicalRecordResource(
            $record->fresh(['histories', 'conditions', 'allergies', 'medications']),
        );
    }
}
