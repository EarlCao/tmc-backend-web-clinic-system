<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the medical certificates table.
     *
     * Certificates reference existing patient/consultation/medical-record
     * data rather than duplicating it: patient/staff are stored as display
     * values (consistent with the Appointments and Consultations tables),
     * and the FK columns keep the certificate traceable back to the source
     * records. `issue_date` is the printed date of the certificate;
     * `valid_until` is optional (e.g. fitness/clearance certificates).
     */
    public function up(): void
    {
        Schema::create('medical_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('patient');
            $table->string('patient_id')->nullable();
            $table->foreignId('consultation_id')->nullable()->constrained('consultations')->nullOnDelete();
            $table->foreignId('medical_record_id')->nullable()->constrained('medical_records')->nullOnDelete();
            $table->string('issued_by')->nullable();
            $table->string('purpose');
            $table->string('diagnosis')->nullable();
            $table->text('recommendation')->nullable();
            $table->date('issue_date');
            $table->date('valid_until')->nullable();
            $table->string('status')->default('Issued')->index();
            $table->timestamps();

            $table->index(['issue_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_certificates');
    }
};
