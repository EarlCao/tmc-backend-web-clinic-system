<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'reference', 'patient', 'patient_id', 'consultation_id', 'medical_record_id',
    'issued_by', 'purpose', 'diagnosis', 'recommendation', 'issue_date',
    'valid_until', 'status',
])]
class MedicalCertificate extends Model
{
    /**
     * Certificate lifecycle statuses.
     */
    public const STATUSES = ['Issued', 'Void'];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date:Y-m-d',
            'valid_until' => 'date:Y-m-d',
        ];
    }

    /**
     * The consultation this certificate was generated from, when applicable.
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class, 'consultation_id');
    }

    /**
     * The medical record this certificate draws patient history from, when applicable.
     */
    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class, 'medical_record_id');
    }

    /**
     * Next sequential reference for the given issue date, e.g. MC-2026-001.
     * Mirrors Consultation::nextReference.
     */
    public static function nextReference(string $date, bool $lock = false): string
    {
        $year = date('Y', strtotime($date));
        $prefix = "MC-{$year}-";
        $query = static::query()->where('reference', 'like', $prefix.'%');

        if ($lock) {
            $query->lockForUpdate();
        }

        $max = $query->max('reference');
        $next = $max ? ((int) substr($max, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
