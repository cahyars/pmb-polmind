<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30)->default('bank_transfer'); // bank_transfer, virtual_account, ewallet, qris, cash
            $table->string('provider'); // contoh: Bank BCA, Bank Mandiri, DANA
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('payment_method_id')->nullable()->after('invoice_id')
                ->constrained('payment_methods')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            // Penanda badge "camaba baru" di panel admin.
            $table->timestamp('applicants_seen_at')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('applicants_seen_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_method_id');
        });

        Schema::dropIfExists('payment_methods');
    }
};
