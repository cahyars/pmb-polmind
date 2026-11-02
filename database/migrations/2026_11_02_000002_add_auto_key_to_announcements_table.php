<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Penanda pengumuman otomatis sistem (contoh ambil_kaos), satu per Tahun PMB.
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('auto_key', 50)->nullable()->after('pmb_year_id');
            $table->unique(['auto_key', 'pmb_year_id']);
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropUnique(['auto_key', 'pmb_year_id']);
            $table->dropColumn('auto_key');
        });
    }
};
