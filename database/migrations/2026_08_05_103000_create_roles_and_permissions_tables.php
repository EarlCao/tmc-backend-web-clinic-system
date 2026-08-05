<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the roles & permissions schema (Module 2).
     *
     * Phase 1 kept a `role` string on users as a stopgap. This migration
     * formalizes it: it inserts the base system roles, backfills users onto
     * them from the old string, then drops the legacy column so the
     * `role_id` foreign key becomes the single source of truth.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('module')->index();
            $table->string('name')->unique();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('email')->constrained()->nullOnDelete();
        });

        // Base system roles (the migration inserts them so the backfill below
        // can resolve foreign keys before any seeder runs).
        $roleIds = [
            'admin' => DB::table('roles')->insertGetId([
                'name' => 'admin',
                'description' => 'Administrator — full system access',
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]),
            'doctor' => DB::table('roles')->insertGetId([
                'name' => 'doctor',
                'description' => 'Doctor — clinical care and records',
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]),
            'nurse' => DB::table('roles')->insertGetId([
                'name' => 'nurse',
                'description' => 'Nurse — clinic care support',
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]),
            'staff' => DB::table('roles')->insertGetId([
                'name' => 'staff',
                'description' => 'Staff — general clinic staff',
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]),
        ];

        // Backfill existing users from the legacy role string.
        foreach ($roleIds as $legacy => $roleId) {
            DB::table('users')->where('role', $legacy)->update(['role_id' => $roleId]);
        }
        DB::table('users')->whereNull('role_id')->update(['role_id' => $roleIds['staff']]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('staff')->after('email');
        });

        DB::statement('UPDATE users SET role = ? WHERE role_id = (SELECT id FROM roles WHERE name = ? LIMIT 1)', ['admin', 'admin']);
        DB::statement('UPDATE users SET role = ? WHERE role_id IS NOT NULL AND role = ?', ['staff', '']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });

        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
