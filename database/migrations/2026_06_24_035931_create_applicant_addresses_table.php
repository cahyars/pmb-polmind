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
        Schema::create('applicant_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();

            $table->text('address')->nullable();

            $table->string('province_code')->nullable();
            $table->string('province_name')->nullable();

            $table->string('regency_code')->nullable();
            $table->string('regency_name')->nullable();

            $table->string('district_code')->nullable();
            $table->string('district_name')->nullable();

            $table->string('village_code')->nullable();
            $table->string('village_name')->nullable();

            $table->string('postal_code')->nullable();
            $table->string('rt')->nullable();
            $table->string('rw')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicant_addresses');
    }
};
