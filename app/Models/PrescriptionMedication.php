<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'prescription_id', 'medicine_name', 'dosage', 'frequency', 'duration',
    'instructions', 'sort_order',
])]
class PrescriptionMedication extends Model
{
    /**
     * The prescription this medication line belongs to.
     */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class, 'prescription_id');
    }
}
