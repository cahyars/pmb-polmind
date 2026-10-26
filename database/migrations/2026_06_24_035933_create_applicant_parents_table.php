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
        Schema::create('applicant_parents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();

            $table->string('father_name')->nullable();
            $table->string('father_nik', 16)->nullable();
            $table->date('father_birth_date')->nullable();
            $table->unsignedSmallInteger('father_education_id')->nullable(); // ref pendidikan
            $table->unsignedSmallInteger('father_job_id')->nullable(); // ref pekerjaan
            $table->unsignedSmallInteger('father_income_id')->nullable(); // ref penghasilan
            $table->unsignedInteger('father_special_need_id')->default(0);
            $table->string('father_phone')->nullable();

            $table->string('mother_name')->nullable(); // nama ibu kandung
            $table->string('mother_nik', 16)->nullable();
            $table->date('mother_birth_date')->nullable();
            $table->unsignedSmallInteger('mother_education_id')->nullable(); // ref pendidikan
            $table->unsignedSmallInteger('mother_job_id')->nullable(); // ref pekerjaan
            $table->unsignedSmallInteger('mother_income_id')->nullable(); // ref penghasilan
            $table->unsignedInteger('mother_special_need_id')->default(0);
            $table->string('mother_phone')->nullable();

            $table->string('guardian_name')->nullable();
            $table->string('guardian_relation')->nullable();
            $table->string('guardian_nik', 16)->nullable();
            $table->date('guardian_birth_date')->nullable();
            $table->unsignedSmallInteger('guardian_education_id')->nullable(); // ref pendidikan
            $table->unsignedSmallInteger('guardian_job_id')->nullable(); // ref pekerjaan
            $table->unsignedSmallInteger('guardian_income_id')->nullable(); // ref penghasilan
            $table->string('guardian_phone')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicant_parents');
    }
};
