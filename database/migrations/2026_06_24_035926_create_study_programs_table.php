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
        Schema::create('study_programs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // TRPL, TRM, BD
            $table->string('name');
            $table->string('pddikti_code', 20)->nullable(); // kode_prodi PDDikti, contoh 58302
            $table->string('siakad_id', 36)->nullable(); // id_prodi SIAKAD (UUID)
            $table->string('degree')->default('D4');
            $table->integer('quota')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_programs');
    }
};
