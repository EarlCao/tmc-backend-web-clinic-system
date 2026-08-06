<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMedicalCertificateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is enforced by the `permission:medical_certificates.create`
     * middleware on the route; the form request only validates the payload.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient' => ['required', 'string', 'max:255'],
            'patient_id' => ['nullable', 'string', 'max:255'],
            'consultation_id' => ['nullable', 'integer', 'exists:consultations,id'],
            'medical_record_id' => ['nullable', 'integer', 'exists:medical_records,id'],
            'issued_by' => ['nullable', 'string', 'max:255'],
            'purpose' => ['required', 'string', 'max:255'],
            'diagnosis' => ['nullable', 'string', 'max:255'],
            'recommendation' => ['nullable', 'string'],
            'issue_date' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:issue_date'],
        ];
    }
}
