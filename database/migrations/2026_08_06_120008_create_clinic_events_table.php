<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the campus clinic events table.
     *
     * `date` is stored as the display string the UI already uses and the
     * scheduling form accepts (e.g. "Aug 15, 2026"), matching the existing
     * Events widget contract exactly.
     */
    public function up(): void
    {
        Schema::create('clinic_events', function (Blueprint $table) {
            $table->id();
            $table->string('date');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_events');
    }
};
