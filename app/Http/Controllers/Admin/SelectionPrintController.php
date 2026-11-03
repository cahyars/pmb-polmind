<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PicksAdmissionWave;
use App\Http\Controllers\Controller;
use App\Models\SelectionComponent;
use App\Models\StudyProgram;
use App\Services\SelectionGroups;
use App\Services\SelectionNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Seleksi > Cetak: daftar hadir, daftar kelompok peserta, dan kartu peserta untuk hari H tes.
 */
class SelectionPrintController extends Controller
{
    use PicksAdmissionWave;

    /** Batas baris/kartu kosong cadangan untuk peserta yang datang tanpa nomor. */
    private const MAX_BLANK = 60;

    public function index(Request $request)
    {
        $data = $this->pickWave($request);
        $wave = $data['selectedWave'];

        return view('admin.spmb.cetak', $data + [
            'groups' => $wave ? $wave->selectionGroups()->withCount('applicants')->get() : collect(),
            'studyPrograms' => StudyProgram::orderBy('code')->get(),
            'generalCount' => $wave ? SelectionGroups::wherePartner(SelectionGroups::participants($wave), false)->count() : 0,
            'partnerCount' => $wave ? SelectionGroups::wherePartner(SelectionGroups::participants($wave))->count() : 0,
            'unnumbered' => $wave ? SelectionNumber::eligible($wave)->whereNull('selection_sequence')->count() : 0,
        ]);
    }

    /**
     * Daftar hadir: pendaftar umum, pendaftar mitra, atau satu kelompok.
     */
    public function attendance(Request $request)
    {
        ['selectedWave' => $wave] = $this->pickWave($request);
        abort_unless($wave, 404);

        $validated = $request->validate([
            'jenis' => ['nullable', Rule::in(['umum', 'mitra'])],
            'kelompok' => ['nullable', Rule::in($wave->selectionGroups()->pluck('id')->all())],
            'kosong' => ['nullable', 'integer', 'min:0', 'max:' . self::MAX_BLANK],
        ]);

        $group = isset($validated['kelompok']) ? $wave->selectionGroups()->find($validated['kelompok']) : null;
        $partner = $group ? $group->is_partner : ($validated['jenis'] ?? 'umum') === 'mitra';

        $query = SelectionGroups::participants($wave)->with(['education', 'admissionWave']);
        $applicants = ($group ? $query->where('selection_group_id', $group->id) : SelectionGroups::wherePartner($query, $partner))->get();

        return view('print.daftar-hadir', [
            'wave' => $wave,
            'applicants' => $applicants,
            'group' => $group,
            'partner' => $partner,
            'blank' => (int) ($validated['kosong'] ?? 0),
            'nextSequence' => SelectionNumber::nextSequence($wave, $partner),
        ]);
    }

    /**
     * Daftar Kelompok Peserta: anggota tiap kelompok dengan kolom paraf per tes, untuk pegangan PIC tes.
     * Kelompok mitra dicetak di lembar terpisah.
     */
    public function groupSheet(Request $request)
    {
        ['selectedWave' => $wave] = $this->pickWave($request);
        abort_unless($wave, 404);

        $validated = $request->validate([
            'kosong' => ['nullable', 'integer', 'min:0', 'max:' . self::MAX_BLANK],
        ]);

        $groups = $wave->selectionGroups()->with('components')->get();
        $participants = SelectionGroups::participants($wave)->with('admissionWave')->get()->groupBy('selection_group_id');

        return view('print.daftar-kelompok', [
            'wave' => $wave,
            'groups' => $groups,
            'participants' => $participants,
            'components' => SelectionComponent::where('is_active', true)
                ->orWhereIn('id', $groups->flatMap->components->pluck('id'))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'blank' => (int) ($validated['kosong'] ?? 0),
            'nextSequence' => SelectionNumber::nextSequence($wave, false),
        ]);
    }

    /**
     * Kartu Peserta Seleksi: 10 kartu per lembar A4, urut nomor peserta, ditambah kartu kosong
     * untuk peserta yang datang di hari H tetapi belum bernomor.
     */
    public function cards(Request $request)
    {
        ['waves' => $waves, 'selectedWave' => $wave] = $this->pickWave($request);

        $validated = $request->validate([
            'kelompok' => ['nullable', 'integer'],
            'study_program' => ['nullable', 'integer'],
            'from' => ['nullable', 'integer', 'min:1'],
            'to' => ['nullable', 'integer', 'min:1'],
            'kosong' => ['nullable', 'integer', 'min:0', 'max:' . self::MAX_BLANK],
            'hanya_kosong' => ['nullable', 'boolean'],
        ]);

        $applicants = $wave && ! $request->boolean('hanya_kosong')
            ? SelectionGroups::participants($wave)
                ->with(['studyProgram', 'admissionWave', 'pmbYear'])
                ->when($validated['kelompok'] ?? null, fn ($query, $groupId) => $query->where('selection_group_id', $groupId))
                ->when($validated['study_program'] ?? null, fn ($query, $programId) => $query->where('study_program_id', $programId))
                ->when($validated['from'] ?? null, fn ($query, $from) => $query->where('selection_sequence', '>=', $from))
                ->when($validated['to'] ?? null, fn ($query, $to) => $query->where('selection_sequence', '<=', $to))
                ->get()
            : collect();

        // Kartu kosong ditambahkan sebagai null agar ikut tersusun 10 per lembar.
        $cards = $applicants->concat(array_fill(0, (int) ($validated['kosong'] ?? 0), null));

        return view('print.kartu-peserta', [
            'cards' => $cards,
            'applicantCount' => $applicants->count(),
            'wave' => $wave,
            'waves' => $waves,
            'groups' => $wave ? $wave->selectionGroups()->get() : collect(),
            'pmbYear' => $wave?->pmbYear,
            'studyPrograms' => StudyProgram::orderBy('code')->get(),
            'filters' => $validated,
            'unnumbered' => $wave ? SelectionNumber::eligible($wave)->whereNull('selection_sequence')->count() : 0,
        ]);
    }
}
