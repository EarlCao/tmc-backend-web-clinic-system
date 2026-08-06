<?php

namespace Database\Seeders;

use App\Models\Consultation;
use App\Models\MedicalCertificate;
use App\Models\MedicalRecord;
use Illuminate\Database\Seeder;

class MedicalCertificatesSeeder extends Seeder
{
    /**
     * Seed medical certificates. Idempotent — keyed by reference.
     *
     * Certificates reference the existing Patients/Consultations seed data
     * (matched by patient name / consultation reference) so the module never
     * duplicates data and the FK relationships resolve against real rows.
     */
    public function run(): void
    {
        $certificates = [
            [
                'reference' => 'MC-2026-001',
                'patient' => 'Angela Reyes', 'patient_id' => '2023-0104',
                'consultation_ref' => 'CONS-2026-001',
                'issued_by' => 'Dr. R. Mendoza',
                'purpose' => 'Medical Excuse — Clinic Visit',
                'diagnosis' => 'Tension Headache due to fatigue',
                'recommendation' => 'Excused from classes and physical activities for 24 hours. Advised rest, hydration, and paracetamol as needed. Return to clinic if headache persists.',
                'issue_date' => '2026-07-30', 'valid_until' => null,
                'status' => 'Issued',
            ],
            [
                'reference' => 'MC-2026-002',
                'patient' => 'Joanna Lim', 'patient_id' => '2021-1122',
                'consultation_ref' => 'CONS-2026-002',
                'issued_by' => 'Nurse C. Villanueva',
                'purpose' => 'Medical Excuse — Clinic Visit',
                'diagnosis' => 'Mild Flu Symptoms',
                'recommendation' => 'Superseded — replaced by MC-2026-003.',
                'issue_date' => '2026-07-30', 'valid_until' => null,
                'status' => 'Void',
            ],
            [
                'reference' => 'MC-2026-003',
                'patient' => 'Joanna Lim', 'patient_id' => '2021-1122',
                'consultation_ref' => 'CONS-2026-002',
                'issued_by' => 'Dr. R. Mendoza',
                'purpose' => 'Medical Excuse — Clinic Visit',
                'diagnosis' => 'Mild Flu Symptoms',
                'recommendation' => 'Excused from classes for 2 days. Rest, oral rehydration, and paracetamol 500mg every 4 hours as needed. Monitor for fever spikes.',
                'issue_date' => '2026-07-31', 'valid_until' => null,
                'status' => 'Issued',
            ],
            [
                'reference' => 'MC-2026-004',
                'patient' => 'Mark Dela Cruz', 'patient_id' => '2022-0941',
                'consultation_ref' => 'CONS-2026-003',
                'issued_by' => 'Dr. R. Mendoza',
                'purpose' => 'Medical Excuse — Clinic Visit',
                'diagnosis' => 'Acid Reflux / GERD Flare-up',
                'recommendation' => 'Excused from class for the rest of the day. Avoid spicy food and late meals; take antacid as prescribed.',
                'issue_date' => '2026-07-29', 'valid_until' => null,
                'status' => 'Issued',
            ],
            [
                'reference' => 'MC-2026-005',
                'patient' => 'Susan Clave', 'patient_id' => 'EMP-119',
                'consultation_ref' => 'CONS-2026-004',
                'issued_by' => 'Nurse C. Villanueva',
                'purpose' => 'Fit to Return',
                'diagnosis' => 'Grade 1 Right Ankle Sprain',
                'recommendation' => 'Cleared for light desk duties. Avoid strenuous walking and lifting for 5 days. Continue R.I.C.E. protocol and Ibuprofen as prescribed.',
                'issue_date' => '2026-07-29', 'valid_until' => '2026-08-03',
                'status' => 'Issued',
            ],
            [
                'reference' => 'MC-2026-006',
                'patient' => 'John Paul Santos', 'patient_id' => '2023-0881',
                'consultation_ref' => null,
                'issued_by' => 'Nurse C. Villanueva',
                'purpose' => 'Medical Clearance',
                'diagnosis' => 'Observation for Fever',
                'recommendation' => 'Cleared to resume classes provided fever is controlled. Hydrate well; report to clinic if temperature exceeds 38.5°C.',
                'issue_date' => '2026-08-02', 'valid_until' => null,
                'status' => 'Issued',
            ],
            [
                'reference' => 'MC-2026-007',
                'patient' => 'Patricia Mae Garcia', 'patient_id' => '2024-0012',
                'consultation_ref' => 'CONS-2026-005',
                'issued_by' => 'Dr. S. Lopez',
                'purpose' => 'School Requirement — Medical Certificate',
                'diagnosis' => 'Dental Caries',
                'recommendation' => 'Temporary filling applied; root canal follow-up advised within 30 days. Cleared for regular activities.',
                'issue_date' => '2026-07-28', 'valid_until' => null,
                'status' => 'Issued',
            ],
        ];

        foreach ($certificates as $certificate) {
            $consultationId = $certificate['consultation_ref']
                ? Consultation::where('reference', $certificate['consultation_ref'])->value('id')
                : null;
            $medicalRecordId = MedicalRecord::where('patient_id', $certificate['patient_id'])->value('id');

            MedicalCertificate::firstOrCreate(
                ['reference' => $certificate['reference']],
                [
                    'patient' => $certificate['patient'],
                    'patient_id' => $certificate['patient_id'],
                    'consultation_id' => $consultationId,
                    'medical_record_id' => $medicalRecordId,
                    'issued_by' => $certificate['issued_by'],
                    'purpose' => $certificate['purpose'],
                    'diagnosis' => $certificate['diagnosis'],
                    'recommendation' => $certificate['recommendation'],
                    'issue_date' => $certificate['issue_date'],
                    'valid_until' => $certificate['valid_until'],
                    'status' => $certificate['status'],
                ],
            );
        }
    }
}
