<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicalRecordAllergyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for recording an allergy on a record.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'allergen' => ['required', 'string', 'max:255'],
            'reaction' => ['nullable', 'string', 'max:255'],
            'severity' => ['nullable', 'string', 'max:255'],
            'dateRecorded' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
