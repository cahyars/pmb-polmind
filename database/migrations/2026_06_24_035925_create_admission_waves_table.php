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
        Schema::create('admission_waves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pmb_year_id')->constrained('pmb_years')->cascadeOnDelete();
            $table->string('code'); // GEL1, GEL2, GEL3
            $table->string('name'); // Gelombang 1
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('registration_fee', 15, 2)->default(0);
            $table->boolean('is_active')->default(false);
            $table->string('status')->default('draft'); // draft, open, closed
            $table->timestamps();

            $table->unique(['pmb_year_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_waves');
    }
};
