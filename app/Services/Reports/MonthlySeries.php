<?php

namespace App\Services\Reports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MonthlySeries
{
    /**
     * Ubah hitungan per bulan (kunci Y-m) menjadi deret berurutan berlabel "Jan 2027".
     * Bulan tanpa data tetap ditampilkan agar tren tidak menipu.
     */
    public static function fill(Collection $countsByMonth): Collection
    {
        if ($countsByMonth->isEmpty()) {
            return collect();
        }

        $months = collect();
        $cursor = Carbon::createFromFormat('Y-m-d', $countsByMonth->keys()->min() . '-01')->startOfDay();
        $last = Carbon::createFromFormat('Y-m-d', $countsByMonth->keys()->max() . '-01')->startOfDay();

        while ($cursor->lte($last)) {
            $months->put($cursor->translatedFormat('M Y'), $countsByMonth->get($cursor->format('Y-m'), 0));
            $cursor->addMonth();
        }

        return $months;
    }
}
