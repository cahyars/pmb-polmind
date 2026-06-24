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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();

            $table->string('invoice_number')->unique();
            $table->string('type');
            // registration, re_registration

            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();

            $table->decimal('total_amount', 15, 2)->default(0);

            $table->string('status')->default('unpaid');
            // unpaid, waiting_verification, paid, rejected, cancelled

            $table->text('note')->nullable();

            $table->timestamps();

            $table->index('type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
