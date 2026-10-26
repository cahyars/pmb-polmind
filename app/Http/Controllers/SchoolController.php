<?php

namespace App\Http\Controllers;

use App\Services\SchoolDirectory;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    /**
     * Daftar SMA/SMK dalam satu kabupaten/kota untuk pencarian sekolah asal.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'regency' => ['required', 'string', 'max:10'],
        ]);

        $schools = SchoolDirectory::forRegency($validated['regency']);

        if ($schools === null) {
            return response()->json([
                'available' => false,
                'schools' => [],
            ]);
        }

        return response()
            ->json([
                'available' => true,
                'schools' => $schools,
            ])
            ->header('Cache-Control', 'private, max-age=3600');
    }
}
