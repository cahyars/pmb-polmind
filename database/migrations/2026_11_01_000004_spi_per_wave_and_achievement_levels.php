<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nominal komponen biaya khusus satu gelombang (mis. SPI Gelombang 1 Rp9.000.000).
        Schema::create('fee_component_wave_amounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_component_id')->constrained('fee_components')->cascadeOnDelete();
            $table->foreignId('admission_wave_id')->constrained('admission_waves')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->unique(['fee_component_id', 'admission_wave_id'], 'fee_wave_amount_unique');
        });

        // Kategori prestasi jalur Prestasi beserta nominal SPI-nya, ditetapkan admin per camaba.
        Schema::create('achievement_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('spi_amount', 15, 2);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('applicants', function (Blueprint $table) {
            $table->foreignId('achievement_level_id')->nullable()->after('registration_path')
                ->constrained('achievement_levels')->nullOnDelete();
        });

        // Diganti pilihan skema cicilan (50:50, 50:25:25, 25:25:25:25) saat admin mengaktifkan cicilan.
        Schema::dropIfExists('fee_installment_templates');
    }

    public function down(): void
    {
        Schema::create('fee_installment_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_component_id')->constrained('fee_components')->cascadeOnDelete();
            $table->foreignId('registration_path_id')->constrained('registration_paths')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->decimal('amount', 15, 2);
            $table->date('due_date');
            $table->timestamps();

            $table->unique(['fee_component_id', 'registration_path_id', 'sequence'], 'fee_installment_template_unique');
        });

        Schema::table('applicants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('achievement_level_id');
        });

        Schema::dropIfExists('achievement_levels');
        Schema::dropIfExists('fee_component_wave_amounts');
    }
};
