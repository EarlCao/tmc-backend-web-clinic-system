<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReviewMedicalCertificateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is enforced by the `permission:medical_certificates.approve`
     * (approve/reject) or `permission:medical_certificates.update` (issue)
     * middleware on the route; the form request only validates the payload.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * One request class covers all three workflow actions; each action only
     * reads the fields it needs (`rejection_reason` for reject, `issued_by`
     * and `issue_date` for issue), so the unused rules simply validate
     * against absent optional fields.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => ['nullable', 'string', 'max:2000'],
            'issued_by' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['nullable', 'date'],
        ];
    }
}
