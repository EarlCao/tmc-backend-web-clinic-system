<?php

namespace Database\Seeders;

use App\Models\ClinicEvent;
use Illuminate\Database\Seeder;

class ClinicEventsSeeder extends Seeder
{
    /**
     * Seed upcoming campus health events. Idempotent — keyed by title.
     */
    public function run(): void
    {
        $events = [
            ['date' => 'Aug 03, 2026', 'title' => 'Annual Student Physical Checkup Drive', 'description' => 'Mandatory medical evaluation for incoming first-year college students.'],
            ['date' => 'Aug 07, 2026', 'title' => 'Campus Blood Donation Campaign', 'description' => 'Organized in collaboration with the Philippine Red Cross at the gymnasium.'],
            ['date' => 'Aug 12, 2026', 'title' => 'Mental Health & Wellness Seminar', 'description' => 'A seminar on stress management and academic support for college students.'],
        ];

        foreach ($events as $event) {
            ClinicEvent::firstOrCreate(['title' => $event['title']], $event);
        }
    }
}
