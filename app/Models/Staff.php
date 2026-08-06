<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'role', 'shift', 'status'])]
class Staff extends Model
{
    /**
     * Duty statuses shown by the Dashboard staff roster widget.
     */
    public const STATUSES = ['On duty', 'Break', 'Off duty'];
}
