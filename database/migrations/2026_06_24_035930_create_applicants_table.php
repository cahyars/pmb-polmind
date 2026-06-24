<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pmb_year_id')->constrained('pmb_years')->restrictOnDelete();
            $table->foreignId('admission_wave_id')->nullable()->constrained('admission_waves')->nullOnDelete();
            $table->foreignId('study_program_id')->nullable()->constrained('study_programs')->nullOnDelete();
            $table->foreignId('second_study_program_id')->nullable()->constrained('study_programs')->nullOnDelete();
            $table->foreignId('class_type_id')->nullable()->constrained('class_types')->nullOnDelete();

            $table->string('registration_number')->unique(); // PMB20260001

            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();

            $table->string('nik', 20)->nullable()->unique();
            $table->string('nisn', 20)->nullable();
            $table->string('gender')->nullable(); // male, female
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion')->nullable();

            $table->string('source_information')->nullable(); // Instagram, Roadshow, Teman, dll

            $table->string('registration_status')->default('registrasi_awal');
            $table->string('document_status')->default('belum_upload');
            $table->string('payment_status')->default('belum_bayar');
            $table->string('selection_status')->default('belum_diseleksi');
            $table->string('re_registration_status')->default('belum_daftar_ulang');
            $table->string('sync_status')->default('belum_siap');

            $table->string('nim')->nullable();
            $table->timestamp('synced_at')->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('registration_status');
            $table->index('document_status');
            $table->index('payment_status');
            $table->index('selection_status');
            $table->index('re_registration_status');
            $table->index('sync_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
