<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Zona waktu aplikasi berpindah dari UTC ke Asia/Jakarta. Waktu yang sudah
 * tersimpan ditulis dalam UTC, jadi digeser +7 jam agar tidak tercampur dengan
 * data baru. Kolom tanggal (date) tidak berubah.
 */
return new class extends Migration
{
    private const OFFSET_HOURS = 7;

    // Tabel bawaan framework yang menyimpan waktu sebagai angka atau tidak relevan.
    private const SKIPPED_TABLES = ['migrations', 'cache', 'cache_locks', 'jobs', 'job_batches', 'sessions'];

    public function up(): void
    {
        $this->shift(self::OFFSET_HOURS);
    }

    public function down(): void
    {
        $this->shift(-self::OFFSET_HOURS);
    }

    private function shift(int $hours): void
    {
        $driver = DB::connection()->getDriverName();

        foreach (Schema::getTableListing(schemaQualified: false) as $table) {
            if (in_array($table, self::SKIPPED_TABLES, true)) {
                continue;
            }

            $columns = collect(Schema::getColumns($table))
                ->filter(fn ($column) => in_array(strtolower($column['type_name']), ['timestamp', 'datetime'], true))
                ->pluck('name');

            if ($columns->isEmpty()) {
                continue;
            }

            $grammar = DB::connection()->getQueryGrammar();

            $updates = $columns->mapWithKeys(function ($column) use ($driver, $hours, $grammar) {
                $wrapped = $grammar->wrap($column);

                return [$column => DB::raw($driver === 'sqlite'
                    ? "datetime({$wrapped}, '" . sprintf('%+d', $hours) . " hours')"
                    : "{$wrapped} + INTERVAL {$hours} HOUR")];
            })->all();

            // Satu UPDATE per tabel; kolom NULL tetap NULL di kedua driver.
            DB::table($table)->update($updates);
        }
    }
};
