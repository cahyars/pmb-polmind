<?php

namespace Database\Seeders;

use App\Models\RefOption;
use App\Models\RefRegion;
use Illuminate\Database\Seeder;

/**
 * Referensi biodata dan wilayah dari SIAKAD Polmind (database/data/siakad).
 */
class ReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $references = json_decode(file_get_contents(database_path('data/siakad/referensi.json')), true);

        $now = now();

        foreach ($references as $group => $options) {
            $rows = [];

            foreach (array_values($options) as $index => $option) {
                $rows[] = [
                    'group' => $group,
                    'code' => $option['code'],
                    'name' => $option['name'],
                    'sort_order' => $this->sortOrder($group, $option, $index),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            RefOption::upsert($rows, ['group', 'code'], ['name', 'sort_order', 'updated_at']);
        }

        $regions = json_decode(file_get_contents(database_path('data/siakad/wilayah.json')), true);

        foreach (array_chunk($regions, 500) as $chunk) {
            RefRegion::upsert(
                array_map(fn (array $region) => $region + [
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $chunk),
                ['id'],
                ['level', 'name', 'parent_id', 'updated_at']
            );
        }

        RefOption::flushCache();
    }

    private function sortOrder(string $group, array $option, int $index): int
    {
        // Indonesia di urutan pertama pada pilihan kewarganegaraan.
        if ($group === RefOption::KEWARGANEGARAAN) {
            return $option['code'] === 'ID' ? 0 : $index + 1;
        }

        return $index;
    }
}
