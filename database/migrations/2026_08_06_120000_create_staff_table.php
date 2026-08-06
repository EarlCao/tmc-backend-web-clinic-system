<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the medical staff roster table (today's duty schedule).
     *
     * Mirrors the fields the Dashboard staff widget consumes: name, role,
     * shift, and duty status (On duty / Break / Off duty).
     */
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('role');
            $table->string('shift');
            $table->string('status')->default('On duty');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
