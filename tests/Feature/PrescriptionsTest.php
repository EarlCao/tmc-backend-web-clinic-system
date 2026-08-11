<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\MedicalRecord;
use App\Models\Permission;
use App\Models\Prescription;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrescriptionsTest extends TestCase
{
    use RefreshDatabase;

    private const ALL_PERMISSIONS = [
        'prescriptions.view', 'prescriptions.create',
        'prescriptions.update', 'prescriptions.delete',
    ];

    private const PRESCRIBER_NAME = 'Dr. Test Mendoza';

    /** Create the given permissions (idempotently) and return their ids. */
    private function permissions(array $names): array
    {
        return collect($names)->map(function (string $name) {
            [$module] = explode('.', $name, 2);

            return Permission::firstOrCreate(
                ['name' => $name],
                ['module' => $module, 'label' => ucfirst($name)],
            )->id;
        })->all();
    }

    /** Administrator with the base admin role granted all prescription permissions. */
    private function adminUser(): User
    {
        $role = Role::where('name', 'admin')->firstOrFail();
        $role->permissions()->sync($this->permissions(self::ALL_PERMISSIONS));

        return User::factory()->create(['name' => self::PRESCRIBER_NAME, 'role_id' => $role->id]);
    }

    /** User whose role holds exactly the given permissions. */
    private function userWithPermissions(array $permissionNames): User
    {
        $role = Role::factory()->create();
        $role->permissions()->sync($this->permissions($permissionNames));

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function actingAsUser(User $user): void
    {
        $this->actingAs($user, 'sanctum');
    }

    /** Build a prescription record with sensible defaults for a fresh DB. */
    private function makePrescription(array $overrides = []): Prescription
    {
        $prescription = Prescription::create(array_merge([
            'reference' => Prescription::nextReference('2026-08-10'),
            'patient' => 'Test Patient',
            'patient_id' => '2024-0100',
            'consultation_id' => null,
            'medical_record_id' => null,
            'prescribed_by' => 'Dr. R. Mendoza',
            'date' => '2026-08-10',
        ], $overrides));

        $prescription->medications()->create([
            'medicine_name' => 'Paracetamol',
            'dosage' => '500 mg',
            'frequency' => 'Every 6 hours as needed',
            'duration' => '3 days',
            'instructions' => 'Take with food.',
            'sort_order' => 1,
        ]);

        return $prescription;
    }

    /** A completed consultation for the given patient (for linking tests). */
    private function makeConsultation(string $patient, string $patientId): Consultation
    {
        return Consultation::create([
            'reference' => 'CONS-2026-'.str_pad((string) Consultation::count(), 3, '0', STR_PAD_LEFT),
            'date' => '2026-08-08',
            'time' => '09:00 AM',
            'patient' => $patient,
            'patient_id' => $patientId,
            'staff' => 'Dr. R. Mendoza',
            'status' => 'Completed',
            'chief_complaint' => 'Cough',
            'vitals' => [],
            'clinical_findings' => '',
            'diagnosis' => 'URTI',
            'treatment' => 'Rest',
            'disposition' => 'Sent Home',
            'started_at' => '2026-08-08 09:05 AM',
            'completed_at' => '2026-08-08 09:30 AM',
        ]);
    }

    private function medicationPayload(): array
    {
        return [
            ['medicine_name' => 'Paracetamol', 'dosage' => '500 mg', 'frequency' => 'Every 6 hours as needed', 'duration' => '3 days', 'instructions' => 'Take with food.'],
        ];
    }

    // --- Authentication boundary -------------------------------------------

    public function test_prescription_endpoints_require_authentication(): void
    {
        $this->getJson('/api/prescriptions')->assertUnauthorized();
        $this->getJson('/api/prescriptions/1')->assertUnauthorized();
        $this->postJson('/api/prescriptions', ['patient' => 'X', 'medications' => $this->medicationPayload()])->assertUnauthorized();
        $this->patchJson('/api/prescriptions/1', ['patient' => 'Y'])->assertUnauthorized();
    }

    // --- Authorization boundary --------------------------------------------

    public function test_users_without_view_permission_receive_403(): void
    {
        $user = $this->userWithPermissions(['patients.view']);
        $prescription = $this->makePrescription(); // exists so the show route resolves before the middleware denies

        $this->actingAsUser($user);
        $this->getJson('/api/prescriptions')->assertForbidden();
        $this->getJson("/api/prescriptions/{$prescription->id}")->assertForbidden();
        $this->postJson('/api/prescriptions', ['patient' => 'X', 'medications' => $this->medicationPayload()])->assertForbidden();
    }

    // --- View ---------------------------------------------------------------

    public function test_admin_can_list_prescriptions_with_frontend_shape(): void
    {
        $admin = $this->adminUser();
        $this->makePrescription(['reference' => 'RX-2026-100', 'patient' => 'Frontend Shape Case']);

        $this->actingAsUser($admin);

        $this->getJson('/api/prescriptions')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [[
                    'id', 'reference', 'patient', 'patientId', 'consultationId', 'medicalRecordId',
                    'prescribedBy', 'date', 'medications' => [[
                        'id', 'medicineName', 'dosage', 'frequency', 'duration', 'instructions',
                    ]], 'consultation',
                ]],
                'meta' => ['current_page', 'last_page', 'total', 'per_page'],
            ])
            ->assertJsonPath('data.0.patient', 'Frontend Shape Case')
            ->assertJsonPath('data.0.medications.0.medicineName', 'Paracetamol');
    }

    public function test_list_can_be_searched_by_patient_reference_and_medicine(): void
    {
        $admin = $this->adminUser();
        $this->makePrescription(['reference' => 'RX-2026-201', 'patient' => 'Zed Alpha', 'patient_id' => '2024-0201']);
        $this->makePrescription(['reference' => 'RX-2026-202', 'patient' => 'Zed Beta', 'patient_id' => '2024-0202']);
        $this->makePrescription(['reference' => 'RX-2026-203', 'patient' => 'Other', 'patient_id' => '2024-0203']);

        $this->actingAsUser($admin);

        // By patient name
        $this->getJson('/api/prescriptions?search=Zed')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        // By reference
        $this->getJson('/api/prescriptions?search=RX-2026-201')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.patient', 'Zed Alpha');

        // By medicine name (searches the child lines)
        $this->getJson('/api/prescriptions?search=Paracetamol')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_list_can_filter_by_patient_and_date(): void
    {
        $admin = $this->adminUser();
        $this->makePrescription(['patient' => 'Rica Bautista', 'patient_id' => '2024-0301', 'date' => '2026-08-01']);
        $this->makePrescription(['patient' => 'Another Patient', 'patient_id' => '2024-0302', 'date' => '2026-08-01']);
        $this->makePrescription(['patient' => 'Rica Bautista', 'patient_id' => '2024-0301', 'date' => '2026-08-02']);

        $this->actingAsUser($admin);

        // Patient filter (prescription history for one patient)
        $this->getJson('/api/prescriptions?patient=2024-0301')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.date', '2026-08-02');

        $this->getJson('/api/prescriptions?patient=Rica%20Bautista')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        // Date filter
        $this->getJson('/api/prescriptions?date=2026-08-01')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        // Combined
        $this->getJson('/api/prescriptions?patient=2024-0301&date=2026-08-02')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.patient', 'Rica Bautista');
    }

    public function test_list_is_paginated_with_meta(): void
    {
        $admin = $this->adminUser();
        foreach (range(1, 10) as $i) {
            $this->makePrescription(['reference' => 'RX-2026-3'.str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
        }

        $this->actingAsUser($admin);

        $this->getJson('/api/prescriptions?per_page=4&page=1')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('meta.total', 10)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('meta.per_page', 4)
            ->assertJsonPath('meta.current_page', 1);

        $this->getJson('/api/prescriptions?per_page=4&page=3')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 3);
    }

    public function test_prescription_detail_includes_medications(): void
    {
        $admin = $this->adminUser();
        $prescription = $this->makePrescription(['reference' => 'RX-2026-400']);

        $this->actingAsUser($admin);

        $this->getJson("/api/prescriptions/{$prescription->id}")
            ->assertOk()
            ->assertJsonPath('data.reference', 'RX-2026-400')
            ->assertJsonPath('data.medications.0.medicineName', 'Paracetamol')
            ->assertJsonPath('data.medications.0.dosage', '500 mg');
    }

    // --- Create -------------------------------------------------------------

    public function test_creating_prescription_requires_create_permission(): void
    {
        $viewOnly = $this->userWithPermissions(['prescriptions.view']);

        $this->actingAsUser($viewOnly);
        $this->postJson('/api/prescriptions', ['patient' => 'New Patient', 'medications' => $this->medicationPayload()])->assertForbidden();
    }

    public function test_store_validates_patient_and_medications(): void
    {
        $admin = $this->adminUser();
        $this->actingAsUser($admin);

        $this->postJson('/api/prescriptions', ['medications' => $this->medicationPayload()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['patient']);

        $this->postJson('/api/prescriptions', ['patient' => 'X'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['medications']);

        $this->postJson('/api/prescriptions', ['patient' => 'X', 'medications' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['medications']);

        // Incomplete medication line — dosage and frequency are required
        $this->postJson('/api/prescriptions', [
            'patient' => 'X',
            'medications' => [['medicine_name' => 'Paracetamol']],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['medications.0.dosage', 'medications.0.frequency']);
    }

    public function test_admin_can_create_prescription_with_defaults(): void
    {
        $admin = $this->adminUser();
        $this->actingAsUser($admin);

        $this->postJson('/api/prescriptions', [
            'patient' => 'Rica Bautista',
            'patient_id' => '2024-0401',
            'prescribed_by' => '',
            'medications' => $this->medicationPayload(),
        ])
            ->assertCreated()
            ->assertJsonPath('data.patient', 'Rica Bautista')
            ->assertJsonPath('data.patientId', '2024-0401')
            ->assertJsonPath('data.prescribedBy', self::PRESCRIBER_NAME)
            ->assertJsonPath('data.date', now()->toDateString())
            ->assertJsonPath('data.reference', 'RX-2026-001')
            ->assertJsonPath('data.medications.0.medicineName', 'Paracetamol');

        $this->assertDatabaseHas('prescriptions', [
            'patient' => 'Rica Bautista',
            'reference' => 'RX-2026-001',
            'prescribed_by' => self::PRESCRIBER_NAME,
        ]);
        $this->assertDatabaseHas('prescription_medications', [
            'medicine_name' => 'Paracetamol',
            'sort_order' => 1,
        ]);
    }

    public function test_store_links_consultation_and_composes_into_medical_record(): void
    {
        $admin = $this->adminUser();
        $consultation = $this->makeConsultation('Linked Patient', '2024-0501');
        $record = MedicalRecord::create([
            'patient_id' => '2024-0501', 'name' => 'Linked Patient', 'age' => 20, 'sex' => 'Female',
            'type' => 'Student', 'course_dept' => 'BS CS', 'contact' => '0912-000-0000',
            'emergency_contact' => '', 'status' => 'Active', 'last_updated' => '2026-08-01',
        ]);

        $this->actingAsUser($admin);

        $this->postJson('/api/prescriptions', [
            'patient' => 'Linked Patient',
            'patient_id' => '2024-0501',
            'consultation_id' => $consultation->id,
            'prescribed_by' => 'Dr. R. Mendoza',
            'date' => '2026-08-09',
            'medications' => $this->medicationPayload(),
        ])
            ->assertCreated()
            ->assertJsonPath('data.consultationId', $consultation->id)
            ->assertJsonPath('data.medicalRecordId', $record->id)
            ->assertJsonPath('data.consultation.reference', $consultation->reference)
            ->assertJsonPath('data.date', '2026-08-09');

        $this->assertDatabaseHas('prescriptions', [
            'consultation_id' => $consultation->id,
            'medical_record_id' => $record->id,
            'date' => '2026-08-09',
        ]);
    }

    public function test_store_rejects_consultation_from_a_different_patient(): void
    {
        $admin = $this->adminUser();
        $consultation = $this->makeConsultation('Someone Else', '2024-0601');

        $this->actingAsUser($admin);

        $this->postJson('/api/prescriptions', [
            'patient' => 'Linked Patient',
            'patient_id' => '2024-0501',
            'consultation_id' => $consultation->id,
            'medications' => $this->medicationPayload(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['consultation_id']);
    }

    public function test_store_rejects_medical_record_from_a_different_patient(): void
    {
        $admin = $this->adminUser();
        $record = MedicalRecord::create([
            'patient_id' => '2024-0601', 'name' => 'Someone Else', 'age' => 20, 'sex' => 'Male',
            'type' => 'Student', 'course_dept' => 'BS IT', 'contact' => '',
            'emergency_contact' => '', 'status' => 'Active', 'last_updated' => '2026-08-01',
        ]);

        $this->actingAsUser($admin);

        $this->postJson('/api/prescriptions', [
            'patient' => 'Linked Patient',
            'patient_id' => '2024-0501',
            'medical_record_id' => $record->id,
            'medications' => $this->medicationPayload(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['medical_record_id']);
    }

    // --- Update -------------------------------------------------------------

    public function test_update_requires_update_permission(): void
    {
        $viewCreateOnly = $this->userWithPermissions(['prescriptions.view', 'prescriptions.create']);
        $prescription = $this->makePrescription();

        $this->actingAsUser($viewCreateOnly);
        $this->patchJson("/api/prescriptions/{$prescription->id}", ['patient' => 'X'])->assertForbidden();
    }

    public function test_admin_can_update_prescription_and_replace_medications(): void
    {
        $admin = $this->adminUser();
        $prescription = $this->makePrescription(['reference' => 'RX-2026-500']);

        $this->actingAsUser($admin);

        $this->patchJson("/api/prescriptions/{$prescription->id}", [
            'patient' => 'Updated Patient',
            'prescribed_by' => 'Dr. S. Lopez',
            'date' => '2026-08-11',
            'medications' => [
                ['medicine_name' => 'Amoxicillin', 'dosage' => '500 mg', 'frequency' => 'Every 8 hours', 'duration' => '7 days', 'instructions' => 'Complete the full course.'],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.patient', 'Updated Patient')
            ->assertJsonPath('data.prescribedBy', 'Dr. S. Lopez')
            ->assertJsonPath('data.date', '2026-08-11')
            ->assertJsonCount(1, 'data.medications')
            ->assertJsonPath('data.medications.0.medicineName', 'Amoxicillin');

        $this->assertDatabaseHas('prescriptions', [
            'id' => $prescription->id,
            'patient' => 'Updated Patient',
            'prescribed_by' => 'Dr. S. Lopez',
        ]);
        $this->assertDatabaseMissing('prescription_medications', ['medicine_name' => 'Paracetamol']);
        $this->assertDatabaseHas('prescription_medications', ['medicine_name' => 'Amoxicillin']);
    }

    public function test_update_can_clear_the_consultation_link(): void
    {
        $admin = $this->adminUser();
        $consultation = $this->makeConsultation('Test Patient', '2024-0100');
        $prescription = $this->makePrescription(['consultation_id' => $consultation->id]);

        $this->actingAsUser($admin);

        $this->patchJson("/api/prescriptions/{$prescription->id}", ['consultation_id' => null])
            ->assertOk()
            ->assertJsonPath('data.consultationId', null)
            ->assertJsonPath('data.consultation', null);

        $this->assertDatabaseHas('prescriptions', ['id' => $prescription->id, 'consultation_id' => null]);
    }

    public function test_update_recomposes_into_the_new_patients_medical_record(): void
    {
        $admin = $this->adminUser();
        $oldRecord = MedicalRecord::create([
            'patient_id' => '2024-0701', 'name' => 'Old Patient', 'age' => 20, 'sex' => 'Male',
            'type' => 'Student', 'course_dept' => 'BS IT', 'contact' => '',
            'emergency_contact' => '', 'status' => 'Active', 'last_updated' => '2026-08-01',
        ]);
        $newRecord = MedicalRecord::create([
            'patient_id' => '2024-0702', 'name' => 'New Patient', 'age' => 21, 'sex' => 'Female',
            'type' => 'Student', 'course_dept' => 'BS CS', 'contact' => '',
            'emergency_contact' => '', 'status' => 'Active', 'last_updated' => '2026-08-01',
        ]);
        $prescription = $this->makePrescription([
            'patient' => 'Old Patient', 'patient_id' => '2024-0701',
            'medical_record_id' => $oldRecord->id,
        ]);

        $this->actingAsUser($admin);

        $this->patchJson("/api/prescriptions/{$prescription->id}", [
            'patient' => 'New Patient',
            'patient_id' => '2024-0702',
        ])
            ->assertOk()
            ->assertJsonPath('data.patientId', '2024-0702')
            ->assertJsonPath('data.medicalRecordId', $newRecord->id);

        $this->assertDatabaseHas('prescriptions', [
            'id' => $prescription->id,
            'medical_record_id' => $newRecord->id,
        ]);
    }

    public function test_update_validates_medication_lines_when_replaced(): void
    {
        $admin = $this->adminUser();
        $prescription = $this->makePrescription();

        $this->actingAsUser($admin);

        $this->patchJson("/api/prescriptions/{$prescription->id}", [
            'medications' => [['medicine_name' => 'Only Name']],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['medications.0.dosage']);
    }

    // --- Seeder -------------------------------------------------------------

    public function test_prescriptions_seeder_runs_and_is_idempotent(): void
    {
        $this->seed(\Database\Seeders\ConsultationsSeeder::class);
        $this->seed(\Database\Seeders\PrescriptionsSeeder::class);
        // Re-seeding must not duplicate records (keyed by reference).
        $this->seed(\Database\Seeders\PrescriptionsSeeder::class);

        $this->assertSame(8, Prescription::count());
        $this->assertDatabaseCount('prescription_medications', 12);

        // The seeded prescriptions link the seeded consultations by reference.
        $consultationId = Consultation::where('reference', 'CONS-2026-001')->value('id');
        $this->assertDatabaseHas('prescriptions', [
            'reference' => 'RX-2026-001',
            'patient' => 'Angela Reyes',
            'consultation_id' => $consultationId,
        ]);
    }

    // --- Reference generation ----------------------------------------------

    public function test_references_increment_sequentially_per_year(): void
    {
        $admin = $this->adminUser();
        $this->actingAsUser($admin);

        $this->postJson('/api/prescriptions', ['patient' => 'One', 'medications' => $this->medicationPayload()])->assertCreated();
        $this->postJson('/api/prescriptions', ['patient' => 'Two', 'medications' => $this->medicationPayload()])
            ->assertCreated()
            ->assertJsonPath('data.reference', 'RX-2026-002');
    }
}
