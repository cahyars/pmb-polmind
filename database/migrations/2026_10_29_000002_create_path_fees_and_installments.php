<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Komponen daftar ulang yang boleh dicicil (UKT, SPI).
        Schema::table('fee_components', function (Blueprint $table) {
            $table->boolean('is_installable')->default(false)->after('amount');
        });

        // Nominal komponen biaya khusus untuk satu jalur; tanpa baris ini dipakai nominal default.
        Schema::create('fee_component_path_amounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_component_id')->constrained('fee_components')->cascadeOnDelete();
            $table->foreignId('registration_path_id')->constrained('registration_paths')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->unique(['fee_component_id', 'registration_path_id'], 'fee_path_amount_unique');
        });

        // Template skema cicilan per komponen per jalur, dipakai mengisi jadwal camaba.
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

        // Jadwal angsuran satu invoice daftar ulang, hanya untuk camaba yang diberi cicilan admin.
        Schema::create('invoice_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('fee_component_id')->nullable()->constrained('fee_components')->nullOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('sequence');
            $table->decimal('amount', 15, 2);
            $table->date('due_date');
            $table->timestamps();

            $table->index(['invoice_id', 'due_date']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('installment_enabled_at')->nullable()->after('note');
            $table->string('installment_enabled_by_name')->nullable()->after('installment_enabled_at');
            $table->text('installment_note')->nullable()->after('installment_enabled_by_name');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['installment_enabled_at', 'installment_enabled_by_name', 'installment_note']);
        });

        Schema::dropIfExists('invoice_installments');
        Schema::dropIfExists('fee_installment_templates');
        Schema::dropIfExists('fee_component_path_amounts');

        Schema::table('fee_components', function (Blueprint $table) {
            $table->dropColumn('is_installable');
        });
    }
};
