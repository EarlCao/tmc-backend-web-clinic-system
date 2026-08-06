<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\MedicalCertificate;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicalCertificatesTest extends TestCase
{
    use RefreshDatabase;

    private const ALL_PERMISSIONS = [
        'medical_certificates.view', 'medical_certificates.create',
        'medical_certificates.update', 'medical_certificates.delete',
    ];

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

    /** Administrator with the base admin role granted all certificate permissions. */
    private function adminUser(): User
    {
        $role = Role::where('name', 'admin')->firstOrFail();
        $role->permissions()->sync($this->permissions(self::ALL_PERMISSIONS));

        return User::factory()->create(['role_id' => $role->id]);
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

    /** Build a certificate record with sensible defaults for a fresh DB. */
    private function makeCertificate(array $overrides = []): MedicalCertificate
    {
        return MedicalCertificate::create(array_merge([
            'reference' => MedicalCertificate::nextReference('2026-08-10'),
            'patient' => 'Test Patient',
            'patient_id' => '2024-0100',
            'consultation_id' => null,
            'medical_record_id' => null,
            'issued_by' => 'Dr. R. Mendoza',
            'purpose' => 'Medical Excuse — Clinic Visit',
            'diagnosis' => 'Mild Flu Symptoms',
            'recommendation' => 'Rest and hydration.',
            'issue_date' => '2026-08-10',
            'valid_until' => null,
            'status' => 'Issued',
        ], $overrides));
    }

    // --- Authentication boundary -------------------------------------------

    public function test_certificate_endpoints_require_authentication(): void
    {
        $this->getJson('/api/medical-certificates')->assertUnauthorized();
        $this->getJson('/api/medical-certificates/1')->assertUnauthorized();
        $this->postJson('/api/medical-certificates', ['patient' => 'X', 'purpose' => 'Y'])->assertUnauthorized();
        $this->patchJson('/api/medical-certificates/1', ['status' => 'Void'])->assertUnauthorized();
        $this->deleteJson('/api/medical-certificates/1')->assertUnauthorized();
    }

    // --- Authorization boundary --------------------------------------------

    public function test_users_without_view_permission_receive_403(): void
    {
        $user = $this->userWithPermissions(['patients.view']);

        $this->actingAsUser($user);
        $this->getJson('/api/medical-certificates')->assertForbidden();
        $this->postJson('/api/medical-certificates', ['patient' => 'X', 'purpose' => 'Y'])->assertForbidden();
    }

    // --- View --------------------------------------------------------------

    public function test_admin_can_list_certificates_with_frontend_shape(): void
    {
        $admin = $this->adminUser();
        $this->makeCertificate(['reference' => 'MC-2026-100', 'patient' => 'Frontend Shape Case']);

        $this->actingAsUser($admin);

        $this->getJson('/api/medical-certificates')
            ->assertOk()
            ->assertJsonStructure(['data' => [[
                'id', 'reference', 'patient', 'patientId', 'consultationId', 'medicalRecordId',
                'issuedBy', 'purpose', 'diagnosis', 'recommendation', 'issueDate',
                'validUntil', 'status', 'issuedAt',
            ]]])
            ->assertJsonPath('data.0.patient', 'Frontend Shape Case');
    }

    public function test_list_can_be_filtered_by_status_and_search(): void
    {
        $admin = $this->adminUser();
        $this->makeCertificate(['reference' => 'MC-2026-201', 'patient' => 'Zed Alpha', 'status' => 'Issued', 'diagnosis' => 'Flu']);
        $this->makeCertificate(['reference' => 'MC-2026-202', 'patient' => 'Zed Beta', 'status' => 'Void', 'diagnosis' => 'Sprain']);
        $this->makeCertificate(['reference' => 'MC-2026-203', 'patient' => 'Other', 'status' => 'Issued', 'diagnosis' => 'Flu']);

        $this->actingAsUser($admin);

        $this->getJson('/api/medical-certificates?status=Void')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.patient', 'Zed Beta');

        $this->getJson('/api/medical-certificates?search=Sprain')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.patient', 'Zed Beta');

        $this->getJson('/api/medical-certificates?search=MC-2026-20')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_certificate_detail_can_be_viewed(): void
    {
        $admin = $this->adminUser();
        $certificate = $this->makeCertificate(['reference' => 'MC-2026-300', 'diagnosis' => 'Tension Headache']);

        $this->actingAsUser($admin);

        $this->getJson("/api/medical-certificates/{$certificate->id}")
            ->assertOk()
            ->assertJsonPath('data.reference', 'MC-2026-300')
            ->assertJsonPath('data.diagnosis', 'Tension Headache');
    }

    // --- Generate (store) --------------------------------------------------

    public function test_creating_certificate_requires_create_permission(): void
    {
        $viewOnly = $this->userWithPermissions(['medical_certificates.view']);

        $this->actingAsUser($viewOnly);
        $this->postJson('/api/medical-certificates', ['patient' => 'New Patient', 'purpose' => 'Excuse'])->assertForbidden();
    }

    public function test_store_rejects_missing_patient_and_purpose(): void
    {
        $admin = $this->adminUser();
        $this->actingAsUser($admin);

        $this->postJson('/api/medical-certificates', ['diagnosis' => 'Flu'])->assertUnprocessable();
        $this->postJson('/api/medical-certificates', ['patient' => 'X'])->assertUnprocessable()
            ->assertJsonValidationErrors(['purpose']);
    }

    public function test_admin_can_generate_certificate_with_defaults(): void
    {
        $admin = $this->adminUser();
        $this->actingAsUser($admin);

        $this->postJson('/api/medical-certificates', [
            'patient' => 'Rica Bautista',
            'patient_id' => '2024-0100',
            'issued_by' => 'Dr. R. Mendoza',
            'purpose' => 'Medical Excuse — Clinic Visit',
            'diagnosis' => 'Mild Flu Symptoms',
            'recommendation' => 'Rest for 24 hours.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.patient', 'Rica Bautista')
            ->assertJsonPath('data.status', 'Issued')
            ->assertJsonPath('data.purpose', 'Medical Excuse — Clinic Visit')
            ->assertJsonPath('data.issueDate', now()->toDateString())
            ->assertJsonPath('data.reference', 'MC-2026-001');

        $this->assertDatabaseHas('medical_certificates', [
            'patient' => 'Rica Bautista',
            'reference' => 'MC-2026-001',
            'status' => 'Issued',
        ]);
    }

    public function test_generate_can_link_to_an_existing_consultation(): void
    {
        $admin = $this->adminUser();
        $consultation = Consultation::create([
            'reference' => 'CONS-2026-900',
            'date' => '2026-08-08',
            'time' => '09:00 AM',
            'patient' => 'Linked Patient',
            'patient_id' => '2024-0101',
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

        $this->actingAsUser($admin);

        $this->postJson('/api/medical-certificates', [
            'patient' => 'Linked Patient',
            'patient_id' => '2024-0101',
            'consultation_id' => $consultation->id,
            'purpose' => 'Fit to Return',
            'diagnosis' => 'URTI',
            'issue_date' => '2026-08-09',
            'valid_until' => '2026-08-12',
        ])
            ->assertCreated()
            ->assertJsonPath('data.consultationId', $consultation->id)
            ->assertJsonPath('data.issueDate', '2026-08-09')
            ->assertJsonPath('data.validUntil', '2026-08-12');

        $this->assertDatabaseHas('medical_certificates', [
            'consultation_id' => $consultation->id,
            'issue_date' => '2026-08-09',
            'valid_until' => '2026-08-12',
        ]);
    }

    public function test_valid_until_cannot_precede_issue_date(): void
    {
        $admin = $this->adminUser();
        $this->actingAsUser($admin);

        $this->postJson('/api/medical-certificates', [
            'patient' => 'Bad Date',
            'purpose' => 'Clearance',
            'issue_date' => '2026-08-10',
            'valid_until' => '2026-08-09',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['valid_until']);
    }

    // --- Update ------------------------------------------------------------

    public function test_update_requires_update_permission(): void
    {
        $viewCreateOnly = $this->userWithPermissions(['medical_certificates.view', 'medical_certificates.create']);
        $certificate = $this->makeCertificate();

        $this->actingAsUser($viewCreateOnly);
        $this->patchJson("/api/medical-certificates/{$certificate->id}", ['status' => 'Void'])->assertForbidden();
    }

    public function test_admin_can_update_certificate_fields_and_status(): void
    {
        $admin = $this->adminUser();
        $certificate = $this->makeCertificate(['reference' => 'MC-2026-500']);

        $this->actingAsUser($admin);
        $this->patchJson("/api/medical-certificates/{$certificate->id}", [
            'purpose' => 'Fit to Return',
            'recommendation' => 'Cleared for light duties.',
            'issue_date' => '2026-08-10',
            'valid_until' => '2026-08-15',
            'status' => 'Void',
        ])
            ->assertOk()
            ->assertJsonPath('data.purpose', 'Fit to Return')
            ->assertJsonPath('data.validUntil', '2026-08-15')
            ->assertJsonPath('data.status', 'Void');

        $this->assertDatabaseHas('medical_certificates', [
            'id' => $certificate->id,
            'purpose' => 'Fit to Return',
            'status' => 'Void',
        ]);
    }

    public function test_update_can_clear_nullable_fields(): void
    {
        $admin = $this->adminUser();
        $certificate = $this->makeCertificate([
            'reference' => 'MC-2026-550',
            'valid_until' => '2026-08-20',
            'diagnosis' => 'Old Diagnosis',
        ]);

        $this->actingAsUser($admin);
        $this->patchJson("/api/medical-certificates/{$certificate->id}", [
            'valid_until' => null,
            'diagnosis' => null,
        ])
            ->assertOk()
            ->assertJsonPath('data.validUntil', null)
            ->assertJsonPath('data.diagnosis', '');

        $this->assertDatabaseHas('medical_certificates', [
            'id' => $certificate->id,
            'valid_until' => null,
            'diagnosis' => null,
        ]);
    }

    public function test_update_rejects_invalid_status(): void
    {
        $admin = $this->adminUser();
        $certificate = $this->makeCertificate();

        $this->actingAsUser($admin);
        $this->patchJson("/api/medical-certificates/{$certificate->id}", ['status' => 'Pending'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    // --- Delete ------------------------------------------------------------

    public function test_delete_requires_delete_permission(): void
    {
        $viewUpdateOnly = $this->userWithPermissions([
            'medical_certificates.view', 'medical_certificates.create', 'medical_certificates.update',
        ]);
        $certificate = $this->makeCertificate();

        $this->actingAsUser($viewUpdateOnly);
        $this->deleteJson("/api/medical-certificates/{$certificate->id}")->assertForbidden();
    }

    public function test_admin_can_delete_a_certificate(): void
    {
        $admin = $this->adminUser();
        $certificate = $this->makeCertificate(['reference' => 'MC-2026-600']);

        $this->actingAsUser($admin);
        $this->deleteJson("/api/medical-certificates/{$certificate->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Medical certificate deleted.');

        $this->assertDatabaseMissing('medical_certificates', ['id' => $certificate->id]);
    }

    // --- Reference generation ----------------------------------------------

    public function test_references_increment_sequentially_per_year(): void
    {
        $admin = $this->adminUser();
        $this->actingAsUser($admin);

        $this->postJson('/api/medical-certificates', ['patient' => 'One', 'purpose' => 'Excuse'])->assertCreated();
        $this->postJson('/api/medical-certificates', ['patient' => 'Two', 'purpose' => 'Excuse'])
            ->assertCreated()
            ->assertJsonPath('data.reference', 'MC-2026-002');
    }
}
