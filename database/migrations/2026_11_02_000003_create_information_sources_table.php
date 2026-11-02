<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pilihan awal, sama dengan daftar yang sebelumnya tertulis di kode.
     */
    private const DEFAULTS = ['Instagram', 'TikTok', 'Website', 'Roadshow Sekolah', 'Teman/Keluarga', 'WhatsApp', 'Brosur/Poster', 'Spanduk/Banner', 'Lainnya'];

    public function up(): void
    {
        // Pilihan "Sumber Informasi" di form registrasi; camaba tetap menyimpan namanya sebagai teks.
        Schema::create('information_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('information_sources')->insert(collect(self::DEFAULTS)->map(fn ($name, $index) => [
            'name' => $name,
            'sort_order' => ($index + 1) * 10,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());

        // Isian lama di luar daftar ikut masuk (nonaktif) agar bisa dirapikan dari panel admin.
        $legacy = DB::table('applicants')
            ->whereNotNull('source_information')
            ->where('source_information', '!=', '')
            ->whereNotIn('source_information', self::DEFAULTS)
            ->distinct()
            ->pluck('source_information')
            ->map(fn ($name) => mb_substr(trim($name), 0, 100))
            ->filter()
            ->unique(fn ($name) => mb_strtolower($name));

        DB::table('information_sources')->insert($legacy->values()->map(fn ($name) => [
            'name' => $name,
            'sort_order' => 900,
            'is_active' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('information_sources');
    }
};
