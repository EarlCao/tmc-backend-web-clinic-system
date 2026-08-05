<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a role column to the users table.
     *
     * Phase 1 exposes the authenticated user's identity for the upcoming
     * Roles & Permissions phase; the full RBAC module is intentionally not
     * implemented here. The default keeps existing seeded/legacy users
     * functional until Phase 2 formalizes role assignment.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Least-privilege default; the seeded administrator sets its own
            // 'admin' role explicitly. Phase 2 formalizes role assignment.
            $table->string('role')->default('staff')->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
