<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the medical records table.
     *
     * Each record holds a patient's demographic info plus the clinical
     * sections stored in child tables (medical history, conditions,
     * allergies, medications) so they can be managed independently.
     * `patient_id` links back to the Patients registry's human-readable id.
     */
    public function up(): void
    {
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->string('patient_id')->unique();
            $table->string('name');
            $table->unsignedTinyInteger('age');
            $table->string('sex');
            $table->string('type');
            $table->string('course_dept');
            $table->string('contact')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('status')->default('Active')->index();
            $table->date('last_updated');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
