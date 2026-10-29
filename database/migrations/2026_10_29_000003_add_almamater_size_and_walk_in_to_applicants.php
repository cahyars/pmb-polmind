<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            // S, M, L, XL, XXL, atau ukuran lain yang ditulis camaba (contoh XXXL).
            $table->string('almamater_size', 10)->nullable()->after('special_need_id');

            // Pendaftar yang datang langsung ke kampus diinput oleh admin/operator.
            $table->string('registered_via', 20)->default('online')->after('registration_path');
            $table->string('registered_by_name')->nullable()->after('registered_via');
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn(['almamater_size', 'registered_via', 'registered_by_name']);
        });
    }
};
