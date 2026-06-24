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
        Schema::create('fee_components', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // PENDAFTARAN, DAFTAR_ULANG, SPI, SPP1
            $table->string('name');
            $table->string('type'); // registration, re_registration
            $table->decimal('amount', 15, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_components');
    }
};
