<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link a consultation to the appointment it was started from (Module 4 —
     * Consultations). Nullable: consultations can also be logged directly
     * (e.g. the Dashboard "Log Consultation" form) without an originating
     * appointment. If the appointment is deleted, the link is cleared rather
     * than cascading the clinical record away.
     */
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->foreignId('appointment_id')
                ->nullable()
                ->after('patient_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('appointment_id');
        });
    }
};
