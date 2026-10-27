<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Komponen tes seleksi, contoh: Tes Tertulis, Wawancara.
        Schema::create('selection_components', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Jadwal tiap komponen tes per gelombang, beserta PIC (nama dosen).
        Schema::create('selection_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_wave_id')->constrained('admission_waves')->cascadeOnDelete();
            $table->foreignId('selection_component_id')->constrained('selection_components')->cascadeOnDelete();
            $table->date('test_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location')->nullable(); // contoh: Kampus Polmind Lt. 3 / Online (Zoom)
            $table->string('pic_name')->nullable(); // nama dosen penanggung jawab
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['admission_wave_id', 'test_date']);
        });

        // Tanggal pengumuman dan daftar ulang tiap gelombang.
        Schema::table('admission_waves', function (Blueprint $table) {
            $table->date('announcement_date')->nullable()->after('end_date');
            $table->date('re_registration_start_date')->nullable()->after('announcement_date');
            $table->date('re_registration_end_date')->nullable()->after('re_registration_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('admission_waves', function (Blueprint $table) {
            $table->dropColumn(['announcement_date', 're_registration_start_date', 're_registration_end_date']);
        });

        Schema::dropIfExists('selection_schedules');
        Schema::dropIfExists('selection_components');
    }
};
