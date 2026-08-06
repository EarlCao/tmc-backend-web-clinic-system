<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the dashboard insights table.
     *
     * Holds the two chart datasets the Dashboard renders, keyed by `type`:
     *   'activity'   → { label, percent }  (clinic activity bars)
     *   'peak_hours' → { label, count, percent } (visit volume by time slot)
     */
    public function up(): void
    {
        Schema::create('clinic_insights', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->string('label');
            $table->unsignedInteger('count')->nullable();
            $table->unsignedTinyInteger('percent');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_insights');
    }
};
