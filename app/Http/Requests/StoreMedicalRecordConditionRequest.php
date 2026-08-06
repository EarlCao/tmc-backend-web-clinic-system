<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicalRecordConditionRequest extends FormRequest
{
    /**
     * Authorize all authenticated users — the route-level `permission:`
     * middleware is the authorization boundary for this action.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for adding a diagnosed condition to a record.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'diagnosedDate' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
