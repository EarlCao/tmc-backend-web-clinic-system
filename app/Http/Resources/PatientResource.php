<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    /**
     * Transform the patient into the shape the frontend registry consumes
     * (camelCase keys; `id` is the human-readable registry id).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->patient_id,
            'name' => $this->name,
            'type' => $this->type,
            'courseDept' => $this->course_dept,
            'contact' => $this->contact ?? '',
            'emergencyContact' => $this->emergency_contact ?? '',
            'allergies' => $this->allergies,
            'history' => $this->history,
            'status' => $this->status,
        ];
    }
}
