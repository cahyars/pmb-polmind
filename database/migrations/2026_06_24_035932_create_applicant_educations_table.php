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
        Schema::create('applicant_educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();

            $table->string('school_npsn')->nullable();
            $table->string('school_name')->nullable();
            $table->string('school_type')->nullable(); // SMA, SMK, MA
            $table->string('school_status')->nullable(); // Negeri, Swasta
            $table->text('school_address')->nullable();

            $table->string('major')->nullable(); // RPL, TKJ, IPA, IPS, dll
            $table->year('graduation_year')->nullable();

            $table->decimal('average_score', 5, 2)->nullable();

            $table->boolean('is_manual_school')->default(false);
            $table->string('manual_school_note')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicant_educations');
    }
};
