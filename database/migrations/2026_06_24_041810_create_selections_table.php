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
        Schema::create('selections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();

            $table->decimal('test_score', 5, 2)->nullable();
            $table->decimal('interview_score', 5, 2)->nullable();
            $table->decimal('final_score', 5, 2)->nullable();

            $table->string('status')->default('belum_diseleksi');
            // belum_diseleksi, diterima, cadangan, ditolak

            $table->text('note')->nullable();

            $table->timestamp('decided_at')->nullable();
            $table->string('decided_by_name')->nullable();

            $table->timestamps();

            $table->unique('applicant_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('selections');
    }
};
