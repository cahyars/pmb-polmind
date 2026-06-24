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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();

            $table->string('payment_number')->unique();

            $table->date('transfer_date')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('sender_bank')->nullable();

            $table->decimal('amount', 15, 2)->default(0);

            $table->string('proof_file_name')->nullable();
            $table->string('proof_file_path')->nullable();

            $table->string('status')->default('waiting_verification');
            // waiting_verification, valid, rejected

            $table->text('admin_note')->nullable();

            $table->timestamp('verified_at')->nullable();
            $table->string('verified_by_name')->nullable();

            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
