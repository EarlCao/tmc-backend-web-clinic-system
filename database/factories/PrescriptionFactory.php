<?php

namespace Database\Factories;

use App\Models\Prescription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prescription>
 */
class PrescriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Uses the clinic's recurring patient roster (same names/registry ids the
     * seeders use) so factory records stay consistent with the seeded demo
     * data instead of producing disconnected fake names.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $patient = fake()->randomElement([
            ['name' => 'Angela Reyes', 'id' => '2023-0104'],
            ['name' => 'Mark Dela Cruz', 'id' => '2022-0941'],
            ['name' => 'Joanna Lim', 'id' => '2021-1122'],
            ['name' => 'Susan Clave', 'id' => 'EMP-119'],
            ['name' => 'John Paul Santos', 'id' => '2023-0881'],
            ['name' => 'Patricia Mae Garcia', 'id' => '2024-0012'],
        ]);
        $date = fake()->dateTimeBetween('-90 days', 'today');

        return [
            'reference' => 'RX-'.date('Y', $date->getTimestamp()).'-'.str_pad(
                (string) fake()->unique()->numberBetween(1, 999),
                3,
                '0',
                STR_PAD_LEFT,
            ),
            'patient' => $patient['name'],
            'patient_id' => $patient['id'],
            'consultation_id' => null,
            'medical_record_id' => null,
            'prescribed_by' => fake()->randomElement(['Dr. R. Mendoza', 'Dr. S. Lopez', 'Nurse C. Villanueva', 'Nurse J. Santos']),
            'prescription_date' => $date->format('Y-m-d'),
        ];
    }
}
