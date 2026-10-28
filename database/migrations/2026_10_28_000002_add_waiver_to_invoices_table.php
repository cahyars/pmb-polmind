<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pembebasan biaya formulir oleh admin PMB atas perintah pimpinan.
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('waived_at')->nullable()->after('status');
            $table->string('waived_by_name')->nullable()->after('waived_at');
            $table->text('waiver_reason')->nullable()->after('waived_by_name');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['waived_at', 'waived_by_name', 'waiver_reason']);
        });
    }
};
