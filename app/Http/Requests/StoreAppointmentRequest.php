<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'patient_id' => ['nullable', 'string', 'max:50'],
            'type' => ['required', Rule::in(Appointment::TYPES)],
            'reason' => ['required', 'string', 'max:1000'],
            'date' => ['required', 'date'],
            'time' => ['required', Rule::in(Appointment::TIME_SLOTS)],
            'staff' => ['nullable', 'string', 'max:255'],
        ];
    }
}
