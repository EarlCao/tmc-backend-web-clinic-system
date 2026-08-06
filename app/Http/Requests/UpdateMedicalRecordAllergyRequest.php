<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicalRecordAllergyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * All fields optional so the frontend can send partial updates.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'allergen' => ['nullable', 'string', 'max:255'],
            'reaction' => ['nullable', 'string', 'max:255'],
            'severity' => ['nullable', 'string', 'max:255'],
            'dateRecorded' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
