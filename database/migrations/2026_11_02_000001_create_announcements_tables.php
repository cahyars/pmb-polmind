<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pengumuman admin untuk camaba. Penerima dikunci saat dikirim (announcement_recipients).
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pmb_year_id')->nullable()->constrained('pmb_years')->nullOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('link_url', 500)->nullable();
            $table->string('link_label', 60)->nullable();
            $table->json('audiences');
            $table->foreignId('admission_wave_id')->nullable()->constrained('admission_waves')->nullOnDelete();
            $table->foreignId('study_program_id')->nullable()->constrained('study_programs')->nullOnDelete();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('announcement_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['announcement_id', 'applicant_id']);
            $table->index(['applicant_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_recipients');
        Schema::dropIfExists('announcements');
    }
};
