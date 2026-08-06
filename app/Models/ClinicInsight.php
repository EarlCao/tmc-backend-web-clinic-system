<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'label', 'count', 'percent'])]
class ClinicInsight extends Model
{
    public const TYPE_ACTIVITY = 'activity';
    public const TYPE_PEAK_HOURS = 'peak_hours';
}
