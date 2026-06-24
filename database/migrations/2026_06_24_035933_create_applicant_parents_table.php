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
            $table->string('father_job')->nullable();
            $table->string('father_phone')->nullable();

            $table->string('mother_name')->nullable();
            $table->string('mother_job')->nullable();
            $table->string('mother_phone')->nullable();

            $table->string('guardian_name')->nullable();
            $table->string('guardian_job')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->string('guardian_relation')->nullable();

            $table->string('parent_income_range')->nullable();

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
