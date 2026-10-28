<?php

namespace App\Http\Controllers;

use App\Models\Brochure;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrochureController extends Controller
{
    /**
     * Unduh brosur aktif untuk pengunjung landing page.
     */
    public function download(Brochure $brochure)
    {
        abort_unless($brochure->is_active && Storage::disk('local')->exists($brochure->file_path), 404);

        $brochure->increment('download_count');

        $filename = Str::slug($brochure->title) . '.' . strtolower($brochure->extension);

        return Storage::disk('local')->download($brochure->file_path, $filename, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
