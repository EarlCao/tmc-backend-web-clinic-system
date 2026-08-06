<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the patient registry table.
     *
     * `patient_id` holds the human-readable registry id shown by the UI
     * (e.g. "2023-0104" for students, "EMP-119" for employees), while the
     * table's own auto-increment id stays internal. Allergies/history are
     * short summary strings, matching what the registry cards display.
     */
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('patient_id')->unique();
            $table->string('name');
            $table->string('type');
            $table->string('course_dept');
            $table->string('contact')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('allergies')->default('None');
            $table->string('history')->default('None');
            $table->string('status')->default('Active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
