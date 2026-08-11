<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the prescriptions table.
     *
     * Prescriptions reference existing patient/consultation/medical-record
     * data rather than duplicating it: patient and prescriber are stored as
     * display values (consistent with the Appointments, Consultations, and
     * Medical Certificates tables), while the nullable FK columns keep each
     * prescription traceable back to the consultation it was written during
     * or after and to the patient's medical record. `date` is the date the
     * prescription was written; medication lines live in the child
     * `prescription_medications` table (see its migration).
     */
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('patient');
            $table->string('patient_id')->nullable();
            $table->foreignId('consultation_id')->nullable()->constrained('consultations')->nullOnDelete();
            $table->foreignId('medical_record_id')->nullable()->constrained('medical_records')->nullOnDelete();
            $table->string('prescribed_by')->nullable();
            $table->date('date');
            $table->timestamps();

            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
