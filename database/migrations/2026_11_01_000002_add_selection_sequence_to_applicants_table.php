<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            // Urutan peserta seleksi dalam gelombang; tampil sebagai nomor peserta "{gelombang}.{001}".
            $table->unsignedSmallInteger('selection_sequence')->nullable()->after('selection_status');
            $table->unique(['admission_wave_id', 'selection_sequence']);
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropUnique(['admission_wave_id', 'selection_sequence']);
            $table->dropColumn('selection_sequence');
        });
    }
};
