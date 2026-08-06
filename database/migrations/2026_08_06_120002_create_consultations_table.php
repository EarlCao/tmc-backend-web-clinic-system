<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the clinical consultations table.
     *
     * Follows the consultation workflow the UI expects:
     *   Scheduled → In Progress → Completed.
     * `vitals` is a JSON object (temperature, bloodPressure, pulseRate, etc.)
     * and `started_at`/`completed_at` carry the display timestamps recorded
     * when those workflow steps run. Patient/staff are stored as display
     * values, consistent with the Appointments table.
     */
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->date('date');
            $table->string('time');
            $table->string('patient');
            $table->string('patient_id')->nullable();
            $table->string('staff')->nullable();
            $table->string('status')->default('Scheduled')->index();
            $table->string('chief_complaint')->nullable();
            $table->json('vitals')->nullable();
            $table->text('clinical_findings')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('treatment')->nullable();
            $table->string('disposition')->nullable();
            $table->string('started_at')->nullable();
            $table->string('completed_at')->nullable();
            $table->timestamps();

            $table->index(['date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
