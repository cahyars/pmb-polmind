<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pengaturan yang dapat diubah admin PMB, contoh format NIM.
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::table('study_programs', function (Blueprint $table) {
            // Kode prodi pada NIM, contoh A pada A20270001.
            $table->string('nim_code', 5)->nullable()->after('code');
        });

        Schema::table('applicants', function (Blueprint $table) {
            $table->timestamp('nim_generated_at')->nullable()->after('nim');
            $table->unique('nim');
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropUnique(['nim']);
            $table->dropColumn('nim_generated_at');
        });

        Schema::table('study_programs', function (Blueprint $table) {
            $table->dropColumn('nim_code');
        });

        Schema::dropIfExists('settings');
    }
};
