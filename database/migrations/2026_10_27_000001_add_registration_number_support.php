<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_types', function (Blueprint $table) {
            // Huruf awal nomor pendaftar, contoh A pada A120270001.
            $table->string('registration_code', 1)->nullable()->after('code');
        });

        Schema::create('registration_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pmb_year_id')->constrained('pmb_years')->cascadeOnDelete();
            $table->string('key', 30); // contoh: kelas-A, sementara, invoice-registration
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['pmb_year_id', 'key']);
        });

        Schema::table('applicant_educations', function (Blueprint $table) {
            // id_wilayah kecamatan lokasi sekolah, untuk pencarian sekolah berjenjang.
            $table->string('school_region_id', 10)->nullable()->after('school_status');
        });
    }

    public function down(): void
    {
        Schema::table('applicant_educations', function (Blueprint $table) {
            $table->dropColumn('school_region_id');
        });

        Schema::dropIfExists('registration_sequences');

        Schema::table('class_types', function (Blueprint $table) {
            $table->dropColumn('registration_code');
        });
    }
};
