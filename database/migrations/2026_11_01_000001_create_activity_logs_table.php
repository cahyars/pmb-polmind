<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jejak aktivitas admin, operator, dan camaba. Hanya ditambah, tidak pernah diubah atau dihapus,
        // dan tanpa foreign key agar log tetap ada walau camaba/akunnya dihapus.
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch')->nullable();

            // user (admin/operator), applicant (camaba), guest (login gagal), system.
            $table->string('actor_type', 20);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name');
            $table->string('actor_role', 50)->nullable();
            // Admin yang sedang Login As saat camaba "melakukan" aksi.
            $table->string('impersonator_name')->nullable();

            $table->string('action', 60);
            $table->string('description');

            $table->string('subject_type', 60)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable();
            $table->unsignedBigInteger('applicant_id')->nullable();

            // {"field": {"label": "...", "old": "...", "new": "..."}}
            $table->json('changes')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['actor_type', 'actor_id']);
            $table->index('applicant_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
