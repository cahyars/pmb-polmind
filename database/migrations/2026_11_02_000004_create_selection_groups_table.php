<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kelompok peserta tes per gelombang, diatur Ketua SPMB. Kelompok mitra menampung
        // pendaftar dari SMK Mitra Industri yang dites terpisah.
        Schema::create('selection_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_wave_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_partner')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        // Tes yang diikuti kelompok, contoh kelompok mitra hanya MCU.
        Schema::create('selection_component_selection_group', function (Blueprint $table) {
            // Nama indeks ditulis pendek: nama bawaan melebihi batas 64 karakter MySQL.
            $table->foreignId('selection_group_id')->constrained(indexName: 'scsg_group_foreign')->cascadeOnDelete();
            $table->foreignId('selection_component_id')->constrained(indexName: 'scsg_component_foreign')->cascadeOnDelete();
            $table->primary(['selection_group_id', 'selection_component_id']);
        });

        Schema::table('applicants', function (Blueprint $table) {
            $table->foreignId('selection_group_id')->nullable()->after('selection_sequence')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('selection_group_id');
        });

        Schema::dropIfExists('selection_component_selection_group');
        Schema::dropIfExists('selection_groups');
    }
};
