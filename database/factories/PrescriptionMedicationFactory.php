<?php

namespace Database\Factories;

use App\Models\PrescriptionMedication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrescriptionMedication>
 */
class PrescriptionMedicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Pairs each medicine with the dosage/frequency a clinic would actually
     * write, so factory-generated prescriptions read like real ones rather
     * than random strings.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $medicine = fake()->randomElement([
            ['name' => 'Paracetamol', 'dosage' => '500 mg', 'frequency' => 'Every 6 hours as needed'],
            ['name' => 'Ibuprofen', 'dosage' => '400 mg', 'frequency' => 'Every 8 hours as needed'],
            ['name' => 'Amoxicillin', 'dosage' => '500 mg', 'frequency' => 'Every 8 hours'],
            ['name' => 'Cetirizine', 'dosage' => '10 mg', 'frequency' => 'Once daily'],
            ['name' => 'Omeprazole', 'dosage' => '20 mg', 'frequency' => 'Once daily before breakfast'],
            ['name' => 'Salbutamol Inhaler', 'dosage' => '100 mcg', 'frequency' => 'As needed (max 2 puffs)'],
            ['name' => 'Mefenamic Acid', 'dosage' => '500 mg', 'frequency' => 'Every 6 hours as needed'],
        ]);

        return [
            'medicine_name' => $medicine['name'],
            'dosage' => $medicine['dosage'],
            'frequency' => $medicine['frequency'],
            'duration' => fake()->randomElement(['3 days', '5 days', '7 days', '14 days', 'Until finished', 'As needed']),
            'instructions' => fake()->randomElement([
                'Take with food if stomach upset occurs.',
                'Complete the full course even if symptoms improve.',
                'Do not exceed the stated dose in 24 hours.',
                'May cause drowsiness; avoid driving after taking.',
                'Store in a cool, dry place away from children.',
            ]),
            'sort_order' => 0,
        ];
    }
}
