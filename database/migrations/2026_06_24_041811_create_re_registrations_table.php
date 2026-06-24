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
        Schema::create('re_registrations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();

            $table->string('status')->default('belum_daftar_ulang');
            // belum_daftar_ulang, menunggu_verifikasi, valid, ditolak, lewat_batas

            $table->date('deadline_date')->nullable();

            $table->timestamp('validated_at')->nullable();
            $table->string('validated_by_name')->nullable();

            $table->timestamp('ready_sync_at')->nullable();

            $table->text('admin_note')->nullable();

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
        Schema::dropIfExists('re_registrations');
    }
};
