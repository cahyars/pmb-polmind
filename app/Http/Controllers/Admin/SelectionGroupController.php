<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PicksAdmissionWave;
use App\Http\Controllers\Controller;
use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\SelectionComponent;
use App\Models\SelectionGroup;
use App\Services\SelectionGroups;
use App\Services\SelectionNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Seleksi > Kelompok Tes: Ketua SPMB menyusun kelompok, tes yang diikuti, dan anggota tiap kelompok.
 */
class SelectionGroupController extends Controller
{
    use PicksAdmissionWave;

    public function index(Request $request)
    {
        $data = $this->pickWave($request);
        $wave = $data['selectedWave'];

        $filters = $request->validate([
            'kelompok' => ['nullable', 'string', 'max:20'],
            'jenis' => ['nullable', Rule::in(['umum', 'mitra'])],
            'keyword' => ['nullable', 'string', 'max:100'],
        ]);

        $groups = $wave
            ? $wave->selectionGroups()->with('components')->withCount('applicants')->get()
            : collect();

        $participants = $wave
            ? SelectionGroups::participants($wave)
                ->with(['studyProgram', 'admissionWave', 'education', 'selectionGroup'])
                ->when($filters['kelompok'] ?? null, fn ($query, $group) => $group === 'belum'
                    ? $query->whereNull('selection_group_id')
                    : $query->where('selection_group_id', (int) $group))
                ->when($filters['jenis'] ?? null, fn ($query, $type) => SelectionGroups::wherePartner($query, $type === 'mitra'))
                ->when($filters['keyword'] ?? null, fn ($query, $keyword) => $query->where('full_name', 'like', "%{$keyword}%"))
                ->get()
            : collect();

        return view('admin.spmb.kelompok', $data + [
            'groups' => $groups,
            'participants' => $participants,
            'filters' => $filters,
            'totalParticipants' => $wave ? SelectionGroups::participants($wave)->count() : 0,
            'ungrouped' => $wave ? SelectionGroups::participants($wave)->whereNull('selection_group_id')->count() : 0,
            'partnerCount' => $wave ? SelectionGroups::wherePartner(SelectionGroups::participants($wave))->count() : 0,
            'unnumbered' => $wave ? SelectionNumber::eligible($wave)->whereNull('selection_sequence')->count() : 0,
            'selectionComponents' => SelectionComponent::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function createDefaults(AdmissionWave $admissionWave)
    {
        if ($admissionWave->selectionGroups()->exists()) {
            return $this->back($admissionWave)->withErrors('Kelompok ' . $admissionWave->name . ' sudah ada.');
        }

        SelectionGroups::createDefaults($admissionWave);

        return $this->back($admissionWave)->with('success', 'Kelompok 1-' . SelectionGroups::DEFAULT_GROUPS . ' dan Kelompok Mitra dibuat untuk ' . $admissionWave->name . '.');
    }

    public function store(Request $request, AdmissionWave $admissionWave)
    {
        $validated = $this->validateGroup($request);

        DB::transaction(function () use ($admissionWave, $validated) {
            $admissionWave->selectionGroups()
                ->create(collect($validated)->except('components')->all())
                ->components()->sync($validated['components'] ?? []);
        });

        return $this->back($admissionWave)->with('success', $validated['name'] . ' berhasil ditambahkan.');
    }

    public function update(Request $request, SelectionGroup $selectionGroup)
    {
        $validated = $this->validateGroup($request);

        DB::transaction(function () use ($selectionGroup, $validated) {
            $selectionGroup->update(collect($validated)->except('components')->all());
            $selectionGroup->components()->sync($validated['components'] ?? []);
        });

        return $this->back($selectionGroup->admissionWave)->with('success', $selectionGroup->name . ' berhasil diperbarui.');
    }

    public function destroy(SelectionGroup $selectionGroup)
    {
        $wave = $selectionGroup->admissionWave;
        $members = $selectionGroup->applicants()->count();

        // Per model agar anggota yang dilepas tercatat di Log Aktivitas.
        DB::transaction(function () use ($selectionGroup) {
            $selectionGroup->applicants()->get()->each->update(['selection_group_id' => null]);
            $selectionGroup->delete();
        });

        return $this->back($wave)->with('success', $selectionGroup->name . ' dihapus' . ($members ? '; ' . $members . ' peserta kembali belum berkelompok.' : '.'));
    }

    /**
     * Bagi otomatis: "semua" membagi ulang seluruh peserta, "belum" hanya yang belum berkelompok.
     */
    public function distribute(Request $request, AdmissionWave $admissionWave)
    {
        $validated = $request->validate(['mode' => ['required', Rule::in(['semua', 'belum'])]]);

        if (! $admissionWave->selectionGroups()->where('is_partner', false)->exists()) {
            return $this->back($admissionWave)->withErrors('Buat minimal satu kelompok umum terlebih dahulu.');
        }

        $moved = SelectionGroups::distribute($admissionWave, $validated['mode'] === 'semua');

        return $this->back($admissionWave)->with('success', $moved
            ? $moved . ' peserta ' . $admissionWave->name . ' dimasukkan ke kelompok.'
            : 'Tidak ada peserta yang perlu dipindahkan.');
    }

    /**
     * Pindahkan peserta terpilih ke satu kelompok, atau lepaskan dari kelompok.
     */
    public function move(Request $request, AdmissionWave $admissionWave)
    {
        $validated = $request->validate([
            'applicants' => ['required', 'array'],
            'applicants.*' => ['integer'],
            'target' => ['required', Rule::in(['none', ...$admissionWave->selectionGroups()->pluck('id')->map(fn ($id) => (string) $id)])],
        ], [
            'applicants.required' => 'Centang peserta yang akan dipindahkan.',
            'target.required' => 'Pilih kelompok tujuan.',
        ]);

        $groupId = $validated['target'] === 'none' ? null : (int) $validated['target'];

        $applicants = Applicant::where('admission_wave_id', $admissionWave->id)
            ->whereKey($validated['applicants'])
            ->get()
            ->filter(fn (Applicant $applicant) => $applicant->selection_group_id !== $groupId);

        DB::transaction(fn () => $applicants->each->update(['selection_group_id' => $groupId]));

        $target = $groupId ? SelectionGroup::find($groupId)->name : 'belum berkelompok';

        return $this->back($admissionWave, $request->only('kelompok', 'jenis', 'keyword'))
            ->with('success', $applicants->count() . ' peserta dipindahkan ke ' . $target . '.');
    }

    private function validateGroup(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'is_partner' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'notes' => ['nullable', 'string', 'max:255'],
            'components' => ['nullable', 'array'],
            'components.*' => ['integer', Rule::exists('selection_components', 'id')],
        ], [], ['name' => 'nama kelompok', 'components' => 'tes yang diikuti']);

        $validated['is_partner'] = $request->boolean('is_partner');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        return $validated;
    }

    private function back(?AdmissionWave $wave, array $query = [])
    {
        return redirect()->route('admin.spmb.groups.index', ['gelombang' => $wave?->id] + array_filter($query));
    }
}
