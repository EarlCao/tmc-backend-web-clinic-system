<?php

namespace App\Http\Controllers;

use App\Models\ClinicInsight;
use Illuminate\Http\JsonResponse;

class ClinicInsightsController extends Controller
{
    /**
     * Clinic activity bars: { label, percent } for the Dashboard widget.
     */
    public function activity(): JsonResponse
    {
        $rows = ClinicInsight::where('type', ClinicInsight::TYPE_ACTIVITY)
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'percent' => $row->percent]);

        return response()->json($rows);
    }

    /**
     * Peak visit hours: { label, count, percent } for the Dashboard widget.
     */
    public function peakHours(): JsonResponse
    {
        $rows = ClinicInsight::where('type', ClinicInsight::TYPE_PEAK_HOURS)
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label,
                'count' => $row->count,
                'percent' => $row->percent,
            ]);

        return response()->json($rows);
    }
}
