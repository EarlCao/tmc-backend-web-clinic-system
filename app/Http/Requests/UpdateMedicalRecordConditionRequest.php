<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicalRecordConditionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * All fields optional so the frontend can send partial updates
     * (e.g. a status toggle with only `status`).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'diagnosedDate' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
