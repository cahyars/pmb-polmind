<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\AdmissionWave;
use Illuminate\Http\Request;

/**
 * Gelombang (Tahun PMB terpilih) untuk halaman Seleksi, dipilih lewat ?gelombang=.
 * Default gelombang yang sedang dibuka, lalu gelombang terdekat yang belum berakhir.
 */
trait PicksAdmissionWave
{
    /**
     * @return array{waves: \Illuminate\Support\Collection, selectedWave: ?AdmissionWave, currentWave: ?AdmissionWave}
     */
    protected function pickWave(Request $request, string $parameter = 'gelombang'): array
    {
        $waves = AdmissionWave::orderBy('start_date')->orderBy('id')->get();
        $current = AdmissionWave::current();

        $selectedWave = $waves->firstWhere('id', (int) $request->query($parameter))
            ?? $waves->firstWhere('id', $current?->id)
            ?? $waves->first(fn (AdmissionWave $wave) => $wave->end_date?->gte(now()->startOfDay()))
            ?? $waves->last();

        return [
            'waves' => $waves,
            'selectedWave' => $selectedWave,
            'currentWave' => $current,
        ];
    }
}
