<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the appointments table (Module 3).
     *
     * The table mirrors the fields the existing Appointments frontend already
     * consumes (patient, patient_id, type, reason, date, time, staff, status,
     * notes, requested_on). No dedicated Patient/Staff/Schedule entities exist
     * yet in the domain, so patient/staff are stored as display values, exactly
     * as the current UI treats them. Future modules (Patients, Doctor/Nurse
     * Schedule) can formalize those into foreign keys without touching this
     * table's shape.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('patient');
            $table->string('patient_id')->nullable();
            $table->string('type');
            $table->string('reason');
            $table->date('date');
            $table->string('time');
            $table->string('staff')->nullable();
            $table->string('status')->default('Pending')->index();
            $table->text('notes')->nullable();
            $table->date('requested_on');
            $table->timestamps();

            $table->index(['date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
