<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel referensi mengikuti kode SIAKAD Polmind (referensi Neo Feeder PDDikti),
     * sehingga ID yang disimpan bisa langsung dikirim ke API SIAKAD.
     */
    public function up(): void
    {
        Schema::create('ref_options', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50); // agama, pendidikan, pekerjaan, penghasilan, dll
            $table->string('code', 20); // ID referensi SIAKAD
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['group', 'code']);
        });

        Schema::create('ref_regions', function (Blueprint $table) {
            $table->string('id', 10)->primary(); // id_wilayah SIAKAD
            $table->unsignedTinyInteger('level'); // 1 provinsi, 2 kab/kota, 3 kecamatan
            $table->string('name');
            $table->string('parent_id', 10)->nullable()->index();
            $table->timestamps();

            $table->index(['level', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ref_regions');
        Schema::dropIfExists('ref_options');
    }
};
