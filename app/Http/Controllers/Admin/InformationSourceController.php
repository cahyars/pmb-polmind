<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\InformationSource;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Kelola pilihan "Sumber Informasi" di form registrasi, terbuka untuk Admin dan Operator PMB (tim marketing).
 */
class InformationSourceController extends Controller
{
    public function index()
    {
        // Jumlah pemakai mengikuti Tahun Akademik yang dipilih di panel.
        $sources = InformationSource::query()->withCount('applicants')->ordered()->get();

        return view('admin.information-sources.index', [
            'sources' => $sources,
            'notFilledCount' => InformationSource::filterApplicants(Applicant::query(), InformationSource::NOT_FILLED_KEY)->count(),
        ]);
    }

    public function store(Request $request)
    {
        $source = InformationSource::create($this->validated($request) + ['is_active' => true]);

        return back()->with('success', 'Sumber informasi "' . $source->name . '" ditambahkan ke form registrasi.');
    }

    public function update(Request $request, InformationSource $informationSource)
    {
        $validated = $this->validated($request, $informationSource);
        $oldName = $informationSource->name;

        DB::transaction(function () use ($informationSource, $validated, $oldName) {
            $informationSource->update($validated);

            // Camaba menyimpan nama sumber, jadi ganti nama ikut memperbarui semua Tahun PMB.
            if ($informationSource->wasChanged('name')) {
                Applicant::withoutGlobalScopes()
                    ->where('source_information', $oldName)
                    ->update(['source_information' => $informationSource->name]);
            }
        });

        return back()->with('success', 'Sumber informasi "' . $informationSource->name . '" berhasil diperbarui.');
    }

    public function toggle(InformationSource $informationSource)
    {
        $informationSource->update(['is_active' => ! $informationSource->is_active]);

        return back()->with('success', 'Sumber informasi "' . $informationSource->name . '" ' . ($informationSource->is_active
            ? 'tampil lagi di form registrasi.'
            : 'disembunyikan dari form registrasi. Data camaba yang sudah memilihnya tetap tercatat.'));
    }

    public function destroy(InformationSource $informationSource)
    {
        if ($informationSource->applicants()->withoutGlobalScopes()->exists()) {
            return back()->withErrors('Sumber informasi "' . $informationSource->name . '" sudah dipilih camaba. Nonaktifkan saja agar laporannya tetap utuh.');
        }

        $informationSource->delete();

        return back()->with('success', 'Sumber informasi "' . $informationSource->name . '" berhasil dihapus.');
    }

    private function validated(Request $request, ?InformationSource $informationSource = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', function (string $attribute, mixed $value, Closure $fail) use ($informationSource) {
                // Beda huruf besar/kecil tetap dianggap sama agar laporan tidak terpecah.
                $taken = InformationSource::query()
                    ->whereRaw('lower(name) = ?', [mb_strtolower(trim((string) $value))])
                    ->when($informationSource, fn ($query) => $query->whereKeyNot($informationSource->id))
                    ->exists();

                if ($taken) {
                    $fail('Sumber informasi dengan nama ini sudah ada.');
                }
            }],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [], [
            'name' => 'nama sumber informasi',
            'sort_order' => 'urutan',
        ]);

        return [
            'name' => trim($validated['name']),
            'sort_order' => $validated['sort_order'] ?? 0,
        ];
    }
}
