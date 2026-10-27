<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1 NIK hanya boleh mendaftar sekali per gelombang, tetapi boleh mendaftar lagi di gelombang lain.
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropUnique(['nik']);
            $table->unique(['nik', 'admission_wave_id'], 'applicants_nik_wave_unique');
        });

        // Skema kelas yang ditampilkan ke camaba saat registrasi, diatur admin PMB.
        Schema::table('class_types', function (Blueprint $table) {
            $table->string('schedule')->nullable()->after('description'); // contoh: Senin-Jumat, 08.00-16.00
            $table->text('scheme')->nullable()->after('schedule'); // satu poin per baris
        });
    }

    public function down(): void
    {
        Schema::table('class_types', function (Blueprint $table) {
            $table->dropColumn(['schedule', 'scheme']);
        });

        Schema::table('applicants', function (Blueprint $table) {
            $table->dropUnique('applicants_nik_wave_unique');
            $table->unique('nik');
        });
    }
};
