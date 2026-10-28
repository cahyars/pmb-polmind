<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nilai seleksi kini diinput per komponen tes (Master Data > Komponen Tes) dengan
 * bobot masing-masing, menggantikan kolom tetap nilai tes dan interview. Prodi
 * tempat camaba diterima (pilihan 1 atau 2) disimpan terpisah dari pilihannya.
 */
return new class extends Migration
{
    // Kolom lama => komponen tes penggantinya beserta bobot formula lama (60% tes + 40% interview).
    private const LEGACY_COLUMNS = [
        'test_score' => ['code' => 'TES_TULIS', 'name' => 'Tes Tertulis', 'weight' => 60, 'sort_order' => 1],
        'interview_score' => ['code' => 'WAWANCARA', 'name' => 'Wawancara', 'weight' => 40, 'sort_order' => 2],
    ];

    public function up(): void
    {
        Schema::table('selection_components', function (Blueprint $table) {
            $table->unsignedSmallInteger('weight')->default(1)->after('description');
        });

        Schema::create('selection_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('selection_id')->constrained('selections')->cascadeOnDelete();
            $table->foreignId('selection_component_id')->constrained('selection_components')->cascadeOnDelete();
            $table->decimal('score', 5, 2);
            $table->timestamps();

            $table->unique(['selection_id', 'selection_component_id']);
        });

        Schema::table('applicants', function (Blueprint $table) {
            $table->foreignId('accepted_study_program_id')->nullable()->after('second_study_program_id')
                ->constrained('study_programs')->nullOnDelete();
        });

        // Bobot 60/40 hanya bila komponen aktif persis Tes Tertulis + Wawancara; selain itu bobot sama rata.
        $activeCodes = DB::table('selection_components')->where('is_active', true)->orderBy('code')->pluck('code')->all();
        $legacyCodes = collect(self::LEGACY_COLUMNS)->pluck('code')->sort()->values()->all();

        if ($activeCodes === $legacyCodes) {
            foreach (self::LEGACY_COLUMNS as $component) {
                DB::table('selection_components')->where('code', $component['code'])->update(['weight' => $component['weight']]);
            }
        }

        foreach (self::LEGACY_COLUMNS as $column => $component) {
            $selections = DB::table('selections')->whereNotNull($column)->get(['id', $column]);

            if ($selections->isEmpty()) {
                continue;
            }

            $componentId = DB::table('selection_components')->where('code', $component['code'])->value('id')
                ?? DB::table('selection_components')->insertGetId([
                    'code' => $component['code'],
                    'name' => $component['name'],
                    'weight' => 1,
                    'sort_order' => $component['sort_order'],
                    // Hanya menampung nilai lama; tidak muncul di form seleksi camaba lain.
                    'is_active' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::table('selection_scores')->insert($selections->map(fn ($selection) => [
                'selection_id' => $selection->id,
                'selection_component_id' => $componentId,
                'score' => $selection->{$column},
                'created_at' => now(),
                'updated_at' => now(),
            ])->all());
        }

        // Keputusan lama selalu menerima di pilihan pertama.
        DB::table('applicants')
            ->where('selection_status', 'diterima')
            ->update(['accepted_study_program_id' => DB::raw('study_program_id')]);

        Schema::table('selections', function (Blueprint $table) {
            $table->dropColumn(array_keys(self::LEGACY_COLUMNS));
        });
    }

    public function down(): void
    {
        Schema::table('selections', function (Blueprint $table) {
            $table->decimal('test_score', 5, 2)->nullable()->after('applicant_id');
            $table->decimal('interview_score', 5, 2)->nullable()->after('test_score');
        });

        foreach (self::LEGACY_COLUMNS as $column => $component) {
            $componentId = DB::table('selection_components')->where('code', $component['code'])->value('id');

            if (! $componentId) {
                continue;
            }

            DB::table('selection_scores')->where('selection_component_id', $componentId)->get()
                ->each(fn ($score) => DB::table('selections')->where('id', $score->selection_id)->update([$column => $score->score]));
        }

        Schema::table('applicants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('accepted_study_program_id');
        });

        Schema::dropIfExists('selection_scores');

        Schema::table('selection_components', function (Blueprint $table) {
            $table->dropColumn('weight');
        });
    }
};
