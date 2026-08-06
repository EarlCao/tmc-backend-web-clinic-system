<?php

namespace App\Http\Controllers;

use App\Http\Resources\ConsultationResource;
use App\Models\Consultation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ConsultationController extends Controller
{
    /**
     * List consultations, newest first.
     */
    public function index(): AnonymousResourceCollection
    {
        return ConsultationResource::collection(
            Consultation::orderByDesc('date')->orderByDesc('time')->get(),
        );
    }

    /**
     * Log a new consultation.
     *
     * Accepts both the full consultation shape and the legacy shape the
     * Dashboard "Log Consultation" form sends (symptoms / vitals.bp / temp /
     * pulse), normalizing the latter exactly like the previous service did.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient' => ['required', 'string', 'max:255'],
            'patientId' => ['nullable', 'string', 'max:255'],
            'staff' => ['nullable', 'string', 'max:255'],
            'symptoms' => ['nullable', 'string'],
            'vitals' => ['nullable', 'array'],
            'chiefComplaint' => ['nullable', 'string'],
            'clinicalFindings' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'treatment' => ['nullable', 'string'],
            'disposition' => ['nullable', 'string'],
            'startedAt' => ['nullable', 'string', 'max:255'],
            'completedAt' => ['nullable', 'string', 'max:255'],
        ]);

        $today = now()->toDateString();
        $nowTime = now()->format('h:i A');

        $vitals = [
            'temperature' => $validated['vitals']['temperature'] ?? $validated['vitals']['temp'] ?? '',
            'bloodPressure' => $validated['vitals']['bloodPressure'] ?? $validated['vitals']['bp'] ?? '',
            'pulseRate' => $validated['vitals']['pulseRate'] ?? $validated['vitals']['pulse'] ?? '',
            'respiratoryRate' => $validated['vitals']['respiratoryRate'] ?? '',
            'height' => $validated['vitals']['height'] ?? '',
            'weight' => $validated['vitals']['weight'] ?? '',
        ];

        $consultation = DB::transaction(function () use ($validated, $today, $nowTime, $vitals) {
            return Consultation::create([
                'reference' => Consultation::nextReference($today, true),
                'date' => $today,
                'time' => $nowTime,
                'patient' => $validated['patient'],
                'patient_id' => $validated['patientId'] ?? null,
                'staff' => $validated['staff'] ?? '',
                'status' => 'Completed',
                'chief_complaint' => $validated['chiefComplaint'] ?? $validated['symptoms'] ?? '',
                'vitals' => $vitals,
                'clinical_findings' => $validated['clinicalFindings'] ?? '',
                'diagnosis' => $validated['diagnosis'] ?? '',
                'treatment' => $validated['treatment'] ?? '',
                'disposition' => $validated['disposition'] ?? '',
                'started_at' => $validated['startedAt'] ?? "$today $nowTime",
                'completed_at' => $validated['completedAt'] ?? "$today $nowTime",
            ]);
        });

        return (new ConsultationResource($consultation))->response()->setStatusCode(201);
    }

    /**
     * Move a Scheduled consultation into In Progress.
     */
    public function start(Consultation $consultation): ConsultationResource|JsonResponse
    {
        if ($consultation->status === 'Completed') {
            return response()->json([
                'message' => 'Completed consultations cannot be started again.',
            ], 409);
        }

        $consultation->update([
            'status' => 'In Progress',
            'started_at' => now()->format('Y-m-d h:i A'),
        ]);

        return new ConsultationResource($consultation);
    }

    /**
     * Persist draft consultation information (chief complaint, vitals, etc.).
     * Completed consultations are read-only.
     */
    public function update(Request $request, Consultation $consultation): ConsultationResource|JsonResponse
    {
        if ($consultation->status === 'Completed') {
            return response()->json([
                'message' => 'Completed consultations are read-only.',
            ], 409);
        }

        $validated = $request->validate([
            'chiefComplaint' => ['nullable', 'string'],
            'vitals' => ['nullable', 'array'],
            'clinicalFindings' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'treatment' => ['nullable', 'string'],
            'disposition' => ['nullable', 'string'],
        ]);

        $consultation->update([
            'chief_complaint' => $validated['chiefComplaint'] ?? $consultation->chief_complaint,
            'vitals' => $validated['vitals'] ?? $consultation->vitals,
            'clinical_findings' => $validated['clinicalFindings'] ?? $consultation->clinical_findings,
            'diagnosis' => $validated['diagnosis'] ?? $consultation->diagnosis,
            'treatment' => $validated['treatment'] ?? $consultation->treatment,
            'disposition' => $validated['disposition'] ?? $consultation->disposition,
        ]);

        return new ConsultationResource($consultation);
    }

    /**
     * Mark a consultation as Completed, persisting final recorded data.
     */
    public function complete(Request $request, Consultation $consultation): ConsultationResource|JsonResponse
    {
        if ($consultation->status === 'Completed') {
            return response()->json([
                'message' => 'This consultation is already completed.',
            ], 409);
        }

        $validated = $request->validate([
            'clinicalFindings' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'treatment' => ['nullable', 'string'],
            'disposition' => ['nullable', 'string'],
            'vitals' => ['nullable', 'array'],
        ]);

        $consultation->update([
            'clinical_findings' => $validated['clinicalFindings'] ?? $consultation->clinical_findings,
            'diagnosis' => $validated['diagnosis'] ?? $consultation->diagnosis,
            'treatment' => $validated['treatment'] ?? $consultation->treatment,
            'disposition' => $validated['disposition'] ?? $consultation->disposition,
            'vitals' => $validated['vitals'] ?? $consultation->vitals,
            'status' => 'Completed',
            'completed_at' => now()->format('Y-m-d h:i A'),
        ]);

        return new ConsultationResource($consultation);
    }
}
