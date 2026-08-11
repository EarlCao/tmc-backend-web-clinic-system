<?php

namespace Database\Seeders;

use App\Models\Consultation;
use App\Models\Prescription;
use Illuminate\Database\Seeder;

class PrescriptionsSeeder extends Seeder
{
    /**
     * Seed prescription records with realistic medication lines. Idempotent —
     * keyed by reference; medication children are seeded only when the parent
     * was just created (same pattern as MedicalRecordsSeeder).
     *
     * Prescriptions reuse the patient/consultation data seeded by the other
     * seeders: each record references the matching patient by registry id and
     * links the consultation it was written during or after where applicable.
     */
    public function run(): void
    {
        $consultationId = fn (string $reference) => Consultation::where('reference', $reference)->value('id');

        $prescriptions = [
            [
                'reference' => 'RX-2026-001', 'patient' => 'Angela Reyes', 'patient_id' => '2023-0104',
                'consultation_id' => $consultationId('CONS-2026-001'),
                'prescribed_by' => 'Dr. R. Mendoza', 'date' => '2026-07-30',
                'medications' => [
                    ['medicine_name' => 'Paracetamol', 'dosage' => '500 mg', 'frequency' => 'Every 6 hours as needed', 'duration' => '3 days', 'instructions' => 'Do not exceed 4 doses in 24 hours.'],
                    ['medicine_name' => 'Ibuprofen', 'dosage' => '400 mg', 'frequency' => 'Every 8 hours as needed', 'duration' => '3 days', 'instructions' => 'Take with food.'],
                ],
            ],
            [
                'reference' => 'RX-2026-002', 'patient' => 'Joanna Lim', 'patient_id' => '2021-1122',
                'consultation_id' => $consultationId('CONS-2026-002'),
                'prescribed_by' => 'Nurse C. Villanueva', 'date' => '2026-07-30',
                'medications' => [
                    ['medicine_name' => 'Paracetamol', 'dosage' => '500 mg', 'frequency' => 'Every 4 hours as needed', 'duration' => '3 days', 'instructions' => 'Do not exceed 4 doses in 24 hours.'],
                    ['medicine_name' => 'Cetirizine', 'dosage' => '10 mg', 'frequency' => 'Once daily', 'duration' => '5 days', 'instructions' => 'May cause drowsiness; take at night if needed.'],
                ],
            ],
            [
                'reference' => 'RX-2026-003', 'patient' => 'Mark Dela Cruz', 'patient_id' => '2022-0941',
                'consultation_id' => $consultationId('CONS-2026-003'),
                'prescribed_by' => 'Dr. R. Mendoza', 'date' => '2026-07-29',
                'medications' => [
                    ['medicine_name' => 'Omeprazole', 'dosage' => '20 mg', 'frequency' => 'Once daily before breakfast', 'duration' => '14 days', 'instructions' => 'Take 30 minutes before a meal.'],
                    ['medicine_name' => 'Antacid Liquid', 'dosage' => '10 ml', 'frequency' => 'As needed for heartburn', 'duration' => '7 days', 'instructions' => 'Shake well before use.'],
                ],
            ],
            [
                'reference' => 'RX-2026-004', 'patient' => 'Susan Clave', 'patient_id' => 'EMP-119',
                'consultation_id' => $consultationId('CONS-2026-004'),
                'prescribed_by' => 'Nurse C. Villanueva', 'date' => '2026-07-29',
                'medications' => [
                    ['medicine_name' => 'Ibuprofen', 'dosage' => '400 mg', 'frequency' => 'Every 8 hours', 'duration' => '3 days', 'instructions' => 'Take with food.'],
                ],
            ],
            [
                'reference' => 'RX-2026-005', 'patient' => 'Patricia Mae Garcia', 'patient_id' => '2024-0012',
                'consultation_id' => $consultationId('CONS-2026-005'),
                'prescribed_by' => 'Dr. S. Lopez', 'date' => '2026-07-28',
                'medications' => [
                    ['medicine_name' => 'Analgesic', 'dosage' => '500 mg', 'frequency' => 'Every 6 hours as needed', 'duration' => '2 days', 'instructions' => 'For post-dental procedure pain.'],
                ],
            ],
            [
                'reference' => 'RX-2026-006', 'patient' => 'John Paul Santos', 'patient_id' => '2023-0881',
                'consultation_id' => $consultationId('CONS-2026-013'), // written during the in-progress visit
                'prescribed_by' => 'Nurse C. Villanueva', 'date' => '2026-08-01',
                'medications' => [
                    ['medicine_name' => 'Paracetamol', 'dosage' => '500 mg', 'frequency' => 'Every 6 hours as needed', 'duration' => '3 days', 'instructions' => 'Use for fever and body aches.'],
                    ['medicine_name' => 'Oral Rehydration Salts', 'dosage' => '1 sachet', 'frequency' => 'Every 3 hours', 'duration' => '2 days', 'instructions' => 'Dissolve in 200 ml of water.'],
                ],
            ],
            [
                'reference' => 'RX-2026-007', 'patient' => 'Mark Dela Cruz', 'patient_id' => '2022-0941',
                'consultation_id' => null, // after-visit refill, no new consultation
                'prescribed_by' => 'Dr. R. Mendoza', 'date' => '2026-08-03',
                'medications' => [
                    ['medicine_name' => 'Omeprazole', 'dosage' => '20 mg', 'frequency' => 'Once daily before breakfast', 'duration' => '30 days', 'instructions' => 'Take 30 minutes before a meal.'],
                ],
            ],
            [
                'reference' => 'RX-2026-008', 'patient' => 'Angela Reyes', 'patient_id' => '2023-0104',
                'consultation_id' => null,
                'prescribed_by' => 'Dr. R. Mendoza', 'date' => '2026-08-05',
                'medications' => [
                    ['medicine_name' => 'Salbutamol Inhaler', 'dosage' => '100 mcg', 'frequency' => 'As needed (max 2 puffs)', 'duration' => 'Until finished', 'instructions' => 'Use before exercise or when wheezing.'],
                ],
            ],
        ];

        foreach ($prescriptions as $prescription) {
            $medications = $prescription['medications'];
            unset($prescription['medications']);

            $created = Prescription::firstOrCreate(['reference' => $prescription['reference']], $prescription);

            // Only seed the medication lines once (i.e. when the parent was
            // just created).
            if ($created->wasRecentlyCreated) {
                foreach ($medications as $index => $medication) {
                    $created->medications()->create($medication + ['sort_order' => $index + 1]);
                }
            }
        }
    }
}
