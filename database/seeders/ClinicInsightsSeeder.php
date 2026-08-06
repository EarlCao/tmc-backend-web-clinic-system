<?php

namespace Database\Seeders;

use App\Models\ClinicInsight;
use Illuminate\Database\Seeder;

class ClinicInsightsSeeder extends Seeder
{
    /**
     * Seed the Dashboard chart datasets. Idempotent — keyed by type+label.
     */
    public function run(): void
    {
        $activity = [
            ['type' => 'activity', 'label' => 'Consultations', 'percent' => 78],
            ['type' => 'activity', 'label' => 'Prescriptions', 'percent' => 52],
            ['type' => 'activity', 'label' => 'Certificates', 'percent' => 34],
        ];

        $peakHours = [
            ['type' => 'peak_hours', 'label' => '08:00 AM - 10:00 AM', 'count' => 18, 'percent' => 85],
            ['type' => 'peak_hours', 'label' => '10:00 AM - 12:00 PM', 'count' => 22, 'percent' => 100],
            ['type' => 'peak_hours', 'label' => '12:00 PM - 02:00 PM', 'count' => 8, 'percent' => 36],
            ['type' => 'peak_hours', 'label' => '02:00 PM - 04:00 PM', 'count' => 14, 'percent' => 63],
            ['type' => 'peak_hours', 'label' => '04:00 PM - 06:00 PM', 'count' => 5, 'percent' => 22],
        ];

        foreach ([...$activity, ...$peakHours] as $row) {
            ClinicInsight::firstOrCreate(
                ['type' => $row['type'], 'label' => $row['label']],
                collect($row)->except(['type', 'label'])->all(),
            );
        }
    }
}
