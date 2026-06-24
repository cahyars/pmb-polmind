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
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();

            $table->string('status')->default('belum_dihubungi');
            // belum_dihubungi, sudah_dihubungi, tertarik, ragu_ragu,
            // menunggu_orang_tua, akan_daftar_ulang, tidak_jadi, tidak_aktif

            $table->string('priority')->default('sedang');
            // tinggi, sedang, rendah

            $table->string('contact_method')->nullable();
            // whatsapp, phone, email, direct

            $table->timestamp('contacted_at')->nullable();
            $table->date('next_follow_up_date')->nullable();

            $table->text('note')->nullable();
            $table->string('officer_name')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('priority');
            $table->index('next_follow_up_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};
