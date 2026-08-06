<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'patient_id', 'name', 'type', 'course_dept', 'contact',
    'emergency_contact', 'allergies', 'history', 'status',
])]
class Patient extends Model
{
}
