<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extend medical certificates with the request -> review -> issue workflow.
     *
     * Certificates now start as `Pending` (a request) and move through
     * `Approved`/`Rejected` to `Issued`. The new columns record the audit
     * trail of who requested, who approved, and (for rejected requests) why.
     */
    public function up(): void
    {
        Schema::table('medical_certificates', function (Blueprint $table) {
            $table->string('requested_by')->nullable()->after('issued_by');
            $table->string('approved_by')->nullable()->after('requested_by');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->string('rejected_by')->nullable()->after('approved_at');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
            // New requests default to Pending; existing rows keep their value.
            $table->string('status')->default('Pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_certificates', function (Blueprint $table) {
            $table->string('status')->default('Issued')->change();
            $table->dropColumn(['requested_by', 'approved_by', 'approved_at', 'rejected_by', 'rejected_at', 'rejection_reason']);
        });
    }
};
