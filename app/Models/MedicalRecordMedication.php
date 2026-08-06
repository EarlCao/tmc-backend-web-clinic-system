<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'medical_record_id', 'name', 'dosage', 'frequency', 'route',
    'prescribed_by', 'prescribed_date', 'start_date', 'end_date',
    'status', 'instructions',
])]
class MedicalRecordMedication extends Model
{
    protected function casts(): array
    {
        return [
            'prescribed_date' => 'date:Y-m-d',
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
        ];
    }
}
