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
        Schema::create('integration_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('applicant_id')->nullable()->constrained('applicants')->nullOnDelete();

            $table->string('system_name')->default('SIAKAD');
            $table->string('direction')->default('outbound');
            // inbound, outbound

            $table->string('endpoint')->nullable();
            $table->string('method')->nullable();

            $table->string('status')->default('pending');
            // pending, success, failed

            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();

            $table->text('error_message')->nullable();

            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->index('system_name');
            $table->index('direction');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
    }
};
