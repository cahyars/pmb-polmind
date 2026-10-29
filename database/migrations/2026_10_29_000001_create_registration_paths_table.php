<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jalur pendaftaran yang dikelola admin. applicants.registration_path menyimpan kode jalur.
        Schema::create('registration_paths', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Jalur yang sebelumnya tertulis di kode, agar data camaba lama tetap cocok.
        $paths = [
            ['umum', 'Umum', 'Untuk lulusan SMA/SMK/MA atau sederajat. Mengikuti ujian seleksi sesuai jadwal gelombang.'],
            ['prestasi', 'Prestasi', 'Untuk pendaftar dengan prestasi akademik maupun non-akademik. Lampirkan sertifikat penghargaan.'],
            ['undangan', 'Undangan', 'Untuk siswa yang mendapat rekomendasi atau undangan dari sekolah asal.'],
        ];

        foreach ($paths as $index => [$code, $name, $description]) {
            DB::table('registration_paths')->insert([
                'code' => $code,
                'name' => $name,
                'description' => $description,
                'sort_order' => $index + 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_paths');
    }
};
