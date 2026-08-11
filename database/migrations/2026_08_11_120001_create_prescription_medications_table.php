<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create (or sync) the prescription medications child table.
     *
     * Mirrors the nested-child pattern used by medical record sections
     * (conditions/allergies/medications): each row records the medicine name
     * plus the documented dosage, frequency, duration, and instructions, in
     * the order the prescriber wrote them (`sort_order`).
     *
     * Like the prescriptions table, a version from an earlier schema phase
     * may already exist in an established database — only the missing
     * columns are added in that case.
     */
    public function up(): void
    {
        if (Schema::hasTable('prescription_medications')) {
            if (! Schema::hasColumn('prescription_medications', 'sort_order')) {
                Schema::table('prescription_medications', function (Blueprint $table) {
                    $table->unsignedInteger('sort_order')->nullable()->after('instructions');
                });
            }

            return;
        }

        Schema::create('prescription_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $table->string('medicine_name');
            $table->string('dosage')->nullable();
            $table->string('frequency')->nullable();
            $table->string('duration')->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamps();

            $table->index('prescription_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescription_medications');
    }
};
