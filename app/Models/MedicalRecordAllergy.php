<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['medical_record_id', 'allergen', 'reaction', 'severity', 'date_recorded', 'notes'])]
class MedicalRecordAllergy extends Model
{
    protected function casts(): array
    {
        return [
            'date_recorded' => 'date:Y-m-d',
        ];
    }
}
