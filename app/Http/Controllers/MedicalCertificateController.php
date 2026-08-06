<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicalCertificateRequest;
use App\Http\Requests\UpdateMedicalCertificateRequest;
use App\Http\Resources\MedicalCertificateResource;
use App\Models\MedicalCertificate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class MedicalCertificateController extends Controller
{
    /**
     * List medical certificates, optionally filtered by search/status.
     *
     * The frontend searches/filters/paginates client-side over this list
     * (the established module convention); the query parameters mirror the
     * other module indexes and are available for a future server-side swap.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = MedicalCertificate::query();

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('patient', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhere('diagnosis', 'like', "%{$search}%")
                    ->orWhere('issued_by', 'like', "%{$search}%");
            });
        }

        $status = $request->query('status');
        // 'All' is the client-side sentinel for "no filter".
        if ($status && $status !== 'All') {
            $query->where('status', $status);
        }

        return MedicalCertificateResource::collection(
            $query->orderByDesc('issue_date')->orderByDesc('id')->get(),
        );
    }

    /**
     * Show a single medical certificate.
     */
    public function show(MedicalCertificate $certificate): MedicalCertificateResource
    {
        return new MedicalCertificateResource($certificate);
    }

    /**
     * Generate a new medical certificate.
     *
     * Reuses existing patient/consultation/medical-record data; the reference
     * is assigned sequentially for the issue date (MC-YYYY-NNN) inside the
     * transaction so concurrent generation cannot collide.
     */
    public function store(StoreMedicalCertificateRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $issueDate = $validated['issue_date'] ?? now()->toDateString();

        $certificate = DB::transaction(function () use ($validated, $issueDate) {
            return MedicalCertificate::create([
                'reference' => MedicalCertificate::nextReference($issueDate, true),
                'patient' => $validated['patient'],
                'patient_id' => $validated['patient_id'] ?? null,
                'consultation_id' => $validated['consultation_id'] ?? null,
                'medical_record_id' => $validated['medical_record_id'] ?? null,
                'issued_by' => $validated['issued_by'] ?? '',
                'purpose' => $validated['purpose'],
                'diagnosis' => $validated['diagnosis'] ?? '',
                'recommendation' => $validated['recommendation'] ?? '',
                'issue_date' => $issueDate,
                'valid_until' => $validated['valid_until'] ?? null,
                'status' => 'Issued',
            ]);
        });

        return (new MedicalCertificateResource($certificate))->response()->setStatusCode(201);
    }

    /**
     * Update certificate details (purpose, diagnosis, dates, status, etc.).
     */
    public function update(UpdateMedicalCertificateRequest $request, MedicalCertificate $certificate): MedicalCertificateResource
    {
        $validated = $request->validated();

        // Nullable fields use array_key_exists so an explicit `null` in the
        // payload actually CLEARS the stored value (e.g. removing a validity
        // window) instead of being treated as "not provided".
        foreach (['patient_id', 'consultation_id', 'medical_record_id', 'issued_by', 'diagnosis', 'recommendation', 'valid_until'] as $field) {
            if (array_key_exists($field, $validated)) {
                $certificate->{$field} = $validated[$field];
            }
        }

        // Fields that are never cleared keep the coalescing preserve.
        foreach (['patient', 'purpose', 'issue_date', 'status'] as $field) {
            if (array_key_exists($field, $validated)) {
                $certificate->{$field} = $validated[$field];
            }
        }

        $certificate->save();

        return new MedicalCertificateResource($certificate);
    }

    /**
     * Delete a medical certificate.
     */
    public function destroy(MedicalCertificate $certificate): JsonResponse
    {
        $certificate->delete();

        return response()->json(['message' => 'Medical certificate deleted.']);
    }
}
