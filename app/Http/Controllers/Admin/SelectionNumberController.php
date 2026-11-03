<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PicksAdmissionWave;
use App\Http\Controllers\Controller;
use App\Models\AdmissionWave;
use App\Services\SelectionNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Seleksi > Nomor Peserta: memberi dan menyusun ulang nomor peserta per gelombang.
 */
class SelectionNumberController extends Controller
{
    use PicksAdmissionWave;

    /**
     * Peserta gelombang terpilih per jenis: yang sudah bernomor (urut nomor) dan yang sudah bayar tetapi belum bernomor.
     */
    public function index(Request $request)
    {
        $data = $this->pickWave($request);
        $wave = $data['selectedWave'];
        $relations = ['studyProgram', 'admissionWave', 'education'];

        $lists = collect(['general' => false, 'partner' => true])->map(fn (bool $partner) => $wave ? [
            'numbered' => SelectionNumber::numbered($wave, $partner)->with($relations)->orderBy('selection_sequence')->get(),
            'unnumbered' => SelectionNumber::eligible($wave, $partner)->with($relations)->whereNull('selection_sequence')->get(),
        ] : ['numbered' => collect(), 'unnumbered' => collect()]);

        return view('admin.spmb.nomor-peserta', $data + [
            'lists' => $lists,
            'partnerStart' => $wave ? SelectionNumber::partnerStart($wave) : SelectionNumber::PARTNER_START,
        ]);
    }

    public function assign(Request $request, AdmissionWave $admissionWave)
    {
        $partner = $this->isPartner($request);
        $assigned = SelectionNumber::assignMissing($admissionWave, $partner);
        $label = $partner ? 'pendaftar mitra' : 'pendaftar umum';

        return $this->back($admissionWave, $assigned
            ? $assigned . ' ' . $label . ' ' . $admissionWave->name . ' mendapat nomor peserta baru.'
            : 'Semua ' . $label . ' ' . $admissionWave->name . ' yang sudah bayar formulir sudah bernomor.');
    }

    public function renumber(Request $request, AdmissionWave $admissionWave)
    {
        $partner = $this->isPartner($request);
        $start = $partner
            ? (int) $request->validate(['mulai' => ['required', 'integer', 'min:1', 'max:999']], [], ['mulai' => 'nomor mulai'])['mulai']
            : 1;

        $total = SelectionNumber::renumber($admissionWave, $partner, $start);

        return $this->back($admissionWave, 'Nomor peserta ' . ($partner ? 'mitra ' : 'umum ') . $admissionWave->name . ' disusun ulang: '
            . $total . ' peserta, mulai ' . SelectionNumber::format($admissionWave, $start) . '.');
    }

    private function isPartner(Request $request): bool
    {
        return ($request->validate(['jenis' => ['nullable', Rule::in(['umum', 'mitra'])]])['jenis'] ?? null) === 'mitra';
    }

    private function back(AdmissionWave $admissionWave, string $message)
    {
        return redirect()
            ->route('admin.spmb.numbers.index', ['gelombang' => $admissionWave->id])
            ->with('success', $message);
    }
}
