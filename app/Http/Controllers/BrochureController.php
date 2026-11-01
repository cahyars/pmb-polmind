<?php

namespace App\Http\Controllers;

use App\Models\Brochure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrochureController extends Controller
{
    /**
     * Halaman preview brosur aktif. Pengunjung melihat brosur dulu; unduhan opsional dari halaman ini.
     */
    public function show(Request $request, Brochure $brochure)
    {
        $this->ensureAvailable($brochure);

        // Dihitung sekali per sesi agar muat ulang halaman tidak menggandakan jumlah dilihat.
        $viewed = $request->session()->get('brochures_viewed', []);

        if (! in_array($brochure->id, $viewed, true)) {
            $brochure->increment('view_count');
            $request->session()->put('brochures_viewed', [...$viewed, $brochure->id]);
        }

        return view('public.brochure', [
            'brochure' => $brochure,
            'brochures' => Brochure::published()->get(),
        ]);
    }

    /**
     * Isi file untuk ditampilkan di halaman preview (tidak menambah hitungan).
     */
    public function file(Brochure $brochure)
    {
        $this->ensureAvailable($brochure);

        return Storage::disk('local')->response($brochure->file_path, $brochure->file_name, [
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Unduh brosur aktif.
     */
    public function download(Brochure $brochure)
    {
        $this->ensureAvailable($brochure);

        $brochure->increment('download_count');

        $filename = Str::slug($brochure->title) . '.' . strtolower($brochure->extension);

        return Storage::disk('local')->download($brochure->file_path, $filename, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function ensureAvailable(Brochure $brochure): void
    {
        abort_unless($brochure->is_active && Storage::disk('local')->exists($brochure->file_path), 404);
    }
}
