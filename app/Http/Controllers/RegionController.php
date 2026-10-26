<?php

namespace App\Http\Controllers;

use App\Models\RefRegion;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    /**
     * Daftar wilayah turunan untuk dropdown berjenjang.
     * Tanpa parameter parent: daftar provinsi.
     */
    public function index(Request $request)
    {
        $parent = $request->string('parent')->trim()->toString();

        $regions = RefRegion::query()
            ->when(
                $parent === '',
                fn ($query) => $query->where('level', RefRegion::LEVEL_PROVINCE),
                fn ($query) => $query->where('parent_id', $parent)
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()
            ->json($regions)
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
