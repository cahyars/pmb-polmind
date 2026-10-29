<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionWave;
use App\Models\ClassType;
use App\Models\DocumentType;
use App\Models\FeeComponent;
use App\Models\FeeComponentPathAmount;
use App\Models\FeeInstallmentTemplate;
use App\Models\PmbYear;
use App\Models\RegistrationPath;
use App\Models\SelectionComponent;
use App\Models\Setting;
use App\Models\StudyProgram;
use App\Services\NimGenerator;
use App\Services\ReceiptNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MasterDataController extends Controller
{
    /**
     * Submenu Master Data: slug URL => judul dan keterangan halaman.
     */
    public const SECTIONS = [
        'tahun-pmb' => ['label' => 'Tahun PMB', 'description' => 'Periode penerimaan mahasiswa baru dan tahun akademik.'],
        'gelombang' => ['label' => 'Gelombang', 'description' => 'Periode pendaftaran tiap gelombang dan biaya formulir.'],
        'jadwal-seleksi' => ['label' => 'Jadwal Seleksi', 'description' => 'Jadwal tes, pengumuman, dan daftar ulang tiap gelombang.'],
        'komponen-tes' => ['label' => 'Komponen Tes', 'description' => 'Jenis tes yang diikuti camaba saat seleksi.'],
        'pic-tes' => ['label' => 'PIC Tes Seleksi', 'description' => 'Dosen penanggung jawab setiap jadwal tes seleksi.'],
        'prodi' => ['label' => 'Program Studi', 'description' => 'Program studi, kuota, dan kode PDDikti/SIAKAD.'],
        'jalur' => ['label' => 'Jalur Pendaftaran', 'description' => 'Pilihan jalur pendaftaran yang tampil di landing page dan form registrasi.'],
        'kelas' => ['label' => 'Jenis Kelas', 'description' => 'Skema kelas yang ditampilkan ke camaba saat registrasi.'],
        'berkas' => ['label' => 'Jenis Berkas', 'description' => 'Dokumen yang wajib atau opsional diunggah camaba.'],
        'biaya' => ['label' => 'Komponen Biaya', 'description' => 'Biaya formulir, SPI, UKT, dan komponen daftar ulang.'],
        'penomoran' => ['label' => 'Penomoran', 'description' => 'Format NIM mahasiswa baru dan nomor kwitansi pembayaran.'],
    ];

    public function index()
    {
        return redirect()->route('admin.master-data.show', array_key_first(self::SECTIONS));
    }

    public function show(Request $request, string $section)
    {
        $activePmbYear = PmbYear::where('is_active', true)->first();

        $data = match ($section) {
            'tahun-pmb' => [
                'pmbYears' => PmbYear::orderByDesc('year')->get(),
            ],
            'gelombang' => [
                'pmbYears' => PmbYear::orderByDesc('year')->get(),
                'admissionWaves' => AdmissionWave::with('pmbYear')->orderByDesc('id')->get(),
            ],
            'jadwal-seleksi', 'pic-tes' => $this->scheduleData($request),
            'komponen-tes' => [
                'selectionComponents' => SelectionComponent::withCount('schedules')->orderBy('sort_order')->orderBy('name')->get(),
            ],
            'prodi' => [
                'studyPrograms' => StudyProgram::orderBy('code')->get(),
            ],
            'jalur' => [
                'registrationPaths' => RegistrationPath::withCount('applicants')->orderBy('sort_order')->orderBy('id')->get(),
            ],
            'kelas' => [
                'classTypes' => ClassType::orderBy('code')->get(),
            ],
            'berkas' => [
                'documentTypes' => DocumentType::orderBy('sort_order')->get(),
            ],
            'biaya' => [
                'feeComponents' => FeeComponent::orderBy('type')->orderBy('sort_order')->get(),
                'reRegistrationFees' => FeeComponent::with(['pathAmounts', 'installmentTemplates'])
                    ->where('type', 're_registration')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get(),
                'registrationPaths' => RegistrationPath::orderBy('sort_order')->orderBy('id')->get(),
            ],
            'penomoran' => [
                'studyPrograms' => StudyProgram::orderBy('code')->get(),
                'nimFormat' => NimGenerator::format(),
                'nimDigits' => NimGenerator::digits(),
                'nimScope' => NimGenerator::scope(),
                'receiptFormat' => ReceiptNumber::format(),
                'receiptDigits' => ReceiptNumber::digits(),
                'receiptSigner' => ReceiptNumber::signer(),
            ],
        };

        return view('admin.master-data.show', $data + [
            'section' => $section,
            'sections' => self::SECTIONS,
            'activePmbYear' => $activePmbYear,
        ]);
    }

    /**
     * Gelombang (Tahun PMB terpilih) beserta jadwal tesnya. Gelombang yang ditampilkan
     * dipilih lewat ?gelombang=, default gelombang yang sedang dibuka atau terdekat.
     */
    private function scheduleData(Request $request): array
    {
        $waves = AdmissionWave::with(['selectionSchedules.component'])
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();

        $current = AdmissionWave::current();

        $selectedWave = $waves->firstWhere('id', (int) $request->query('gelombang'))
            ?? $waves->firstWhere('id', $current?->id)
            ?? $waves->first(fn (AdmissionWave $wave) => $wave->end_date?->gte(now()->startOfDay()))
            ?? $waves->last();

        return [
            'waves' => $waves,
            'selectedWave' => $selectedWave,
            'currentWave' => $current,
            'selectionComponents' => SelectionComponent::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
        ];
    }

    public function storeStudyProgram(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:study_programs,code'],
            'nim_code' => ['nullable', 'alpha_num:ascii', 'max:5', 'unique:study_programs,nim_code'],
            'name' => ['required', 'string', 'max:255'],
            'degree' => ['required', 'string', 'max:20'],
            'quota' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        StudyProgram::create([
            'code' => strtoupper($validated['code']),
            'nim_code' => filled($validated['nim_code'] ?? null) ? strtoupper($validated['nim_code']) : null,
            'name' => $validated['name'],
            'degree' => strtoupper($validated['degree']),
            'quota' => $validated['quota'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Program studi berhasil ditambahkan.');
    }

    public function updateStudyProgram(Request $request, StudyProgram $studyProgram)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:study_programs,code,' . $studyProgram->id],
            'nim_code' => ['nullable', 'alpha_num:ascii', 'max:5', 'unique:study_programs,nim_code,' . $studyProgram->id],
            'name' => ['required', 'string', 'max:255'],
            'degree' => ['required', 'string', 'max:20'],
            'quota' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $studyProgram->update([
            'code' => strtoupper($validated['code']),
            'nim_code' => filled($validated['nim_code'] ?? null) ? strtoupper($validated['nim_code']) : null,
            'name' => $validated['name'],
            'degree' => strtoupper($validated['degree']),
            'quota' => $validated['quota'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Program studi berhasil diperbarui.');
    }

    public function updateNumbering(Request $request)
    {
        $validated = $request->validate([
            'nim_format' => ['required', 'string', 'max:50', 'regex:/^([A-Za-z0-9.\-\/]|\{PRODI\}|\{KELAS\}|\{TAHUN\}|\{TAHUN2\}|\{URUT\})+$/'],
            'nim_digits' => ['required', 'integer', 'min:3', 'max:6'],
            'nim_scope' => ['required', Rule::in(array_keys(NimGenerator::SCOPES))],
        ], [
            'nim_format.regex' => 'Format NIM hanya boleh berisi huruf, angka, titik, strip, garis miring, dan kode {PRODI}, {KELAS}, {TAHUN}, {TAHUN2}, {URUT}.',
        ], [
            'nim_format' => 'format NIM',
            'nim_digits' => 'panjang nomor urut',
            'nim_scope' => 'cakupan nomor urut',
        ]);

        if (substr_count($validated['nim_format'], '{URUT}') !== 1) {
            return back()->withErrors('Format NIM wajib memuat kode {URUT} tepat satu kali.')->withInput();
        }

        Setting::put([
            'nim.format' => $validated['nim_format'],
            'nim.digits' => $validated['nim_digits'],
            'nim.scope' => $validated['nim_scope'],
        ]);

        return back()->with('success', 'Pengaturan format NIM berhasil disimpan.');
    }

    public function updateReceiptNumbering(Request $request)
    {
        $validated = $request->validate([
            'receipt_format' => ['required', 'string', 'max:50', 'regex:/^([A-Za-z0-9.\-\/]|\{JENIS\}|\{TAHUN\}|\{BULAN\}|\{ROMAWI\}|\{URUT\})+$/'],
            'receipt_digits' => ['required', 'integer', 'min:3', 'max:8'],
            'receipt_signer_name' => ['nullable', 'string', 'max:100'],
            'receipt_signer_title' => ['required', 'string', 'max:100'],
        ], [
            'receipt_format.regex' => 'Format kwitansi hanya boleh berisi huruf, angka, titik, strip, garis miring, dan kode {JENIS}, {TAHUN}, {BULAN}, {ROMAWI}, {URUT}.',
        ], [
            'receipt_format' => 'format nomor kwitansi',
            'receipt_digits' => 'panjang nomor urut',
            'receipt_signer_name' => 'nama penandatangan',
            'receipt_signer_title' => 'jabatan penandatangan',
        ]);

        if (substr_count($validated['receipt_format'], '{URUT}') !== 1) {
            return back()->withErrors('Format nomor kwitansi wajib memuat kode {URUT} tepat satu kali.')->withInput();
        }

        Setting::put([
            'receipt.format' => $validated['receipt_format'],
            'receipt.digits' => $validated['receipt_digits'],
            'receipt.signer_name' => $validated['receipt_signer_name'] ?? null,
            'receipt.signer_title' => $validated['receipt_signer_title'],
        ]);

        return back()->with('success', 'Pengaturan kwitansi berhasil disimpan. Format nomor baru berlaku untuk kwitansi berikutnya.');
    }

    public function toggleStudyProgram(StudyProgram $studyProgram)
    {
        $studyProgram->update([
            'is_active' => ! $studyProgram->is_active,
        ]);

        return back()->with('success', 'Status program studi berhasil diubah.');
    }

    public function storeFeeComponent(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:fee_components,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:registration,re_registration'],
            'amount' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        FeeComponent::create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'is_installable' => $validated['type'] === 're_registration' && $request->boolean('is_installable'),
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Komponen biaya berhasil ditambahkan.');
    }

    public function updateFeeComponent(Request $request, FeeComponent $feeComponent)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:fee_components,code,' . $feeComponent->id],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:registration,re_registration'],
            'amount' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $feeComponent->update([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'is_installable' => $validated['type'] === 're_registration' && $request->boolean('is_installable'),
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Komponen biaya berhasil diperbarui.');
    }

    /**
     * Nominal daftar ulang dan template skema cicilan untuk satu jalur.
     * Nominal kosong berarti memakai nominal default komponen.
     */
    public function updatePathFees(Request $request, RegistrationPath $registrationPath)
    {
        $fees = FeeComponent::where('type', 're_registration')->where('is_active', true)->orderBy('sort_order')->get();

        $validated = $request->validate([
            'amounts' => ['nullable', 'array'],
            'amounts.*' => ['nullable', 'numeric', 'min:0'],
            'installments' => ['nullable', 'array'],
            'installments.*' => ['array'],
            'installments.*.*.amount' => ['required', 'numeric', 'min:1'],
            'installments.*.*.due_date' => ['required', 'date'],
        ], [], [
            'amounts.*' => 'nominal',
            'installments.*.*.amount' => 'nominal angsuran',
            'installments.*.*.due_date' => 'tanggal jatuh tempo',
        ]);

        $errors = [];

        foreach ($fees as $fee) {
            $amount = $validated['amounts'][$fee->id] ?? null;
            $effective = filled($amount) ? (float) $amount : (float) $fee->amount;
            $rows = $fee->is_installable ? ($validated['installments'][$fee->id] ?? []) : [];

            if ($rows && round(collect($rows)->sum('amount'), 2) !== round($effective, 2)) {
                $errors[] = 'Total angsuran ' . $fee->name . ' harus sama dengan nominalnya: Rp' . number_format($effective, 0, ',', '.') . '.';
            }
        }

        if ($errors) {
            return back()->withErrors($errors)->withInput();
        }

        DB::transaction(function () use ($fees, $validated, $registrationPath) {
            foreach ($fees as $fee) {
                $amount = $validated['amounts'][$fee->id] ?? null;
                $match = ['fee_component_id' => $fee->id, 'registration_path_id' => $registrationPath->id];

                if (filled($amount)) {
                    FeeComponentPathAmount::updateOrCreate($match, ['amount' => $amount]);
                } else {
                    FeeComponentPathAmount::where($match)->delete();
                }

                FeeInstallmentTemplate::where($match)->delete();

                $rows = collect($fee->is_installable ? ($validated['installments'][$fee->id] ?? []) : [])
                    ->sortBy('due_date')
                    ->values();

                foreach ($rows as $index => $row) {
                    FeeInstallmentTemplate::create($match + [
                        'sequence' => $index + 1,
                        'amount' => $row['amount'],
                        'due_date' => $row['due_date'],
                    ]);
                }
            }
        });

        return back()
            ->with('success', 'Biaya daftar ulang jalur ' . $registrationPath->name . ' berhasil disimpan.')
            ->with('fee_path_tab', $registrationPath->id);
    }

    public function toggleFeeComponent(FeeComponent $feeComponent)
    {
        $feeComponent->update([
            'is_active' => ! $feeComponent->is_active,
        ]);

        return back()->with('success', 'Status komponen biaya berhasil diubah.');
    }

    public function storePmbYear(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:pmb_years,code'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:draft,active,closed,archived'],
        ]);

        if ($request->boolean('is_active')) {
            PmbYear::query()->update(['is_active' => false]);
        }

        PmbYear::create([
            'code' => strtoupper($validated['code']),
            'year' => $validated['year'],
            'name' => $validated['name'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'status' => $validated['status'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Tahun PMB berhasil ditambahkan.');
    }

    public function updatePmbYear(Request $request, PmbYear $pmbYear)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:pmb_years,code,' . $pmbYear->id],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:draft,active,closed,archived'],
        ]);

        if ($request->boolean('is_active')) {
            PmbYear::where('id', '!=', $pmbYear->id)->update(['is_active' => false]);
        }

        $pmbYear->update([
            'code' => strtoupper($validated['code']),
            'year' => $validated['year'],
            'name' => $validated['name'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'status' => $validated['status'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Tahun PMB berhasil diperbarui.');
    }

    public function togglePmbYear(PmbYear $pmbYear)
    {
        if (! $pmbYear->is_active) {
            PmbYear::query()->update(['is_active' => false]);

            $pmbYear->update([
                'is_active' => true,
                'status' => 'active',
            ]);
        } else {
            $pmbYear->update([
                'is_active' => false,
            ]);
        }

        return back()->with('success', 'Status aktif Tahun PMB berhasil diubah.');
    }

    public function storeAdmissionWave(Request $request)
    {
        $validated = $request->validate([
            'pmb_year_id' => ['required', 'exists:pmb_years,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('admission_waves', 'code')
                    ->where(fn ($query) => $query->where('pmb_year_id', $request->pmb_year_id)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'registration_fee' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:draft,open,closed'],
        ]);

        if ($request->boolean('is_active')) {
            AdmissionWave::where('pmb_year_id', $validated['pmb_year_id'])
                ->update(['is_active' => false]);
        }

        AdmissionWave::create([
            'pmb_year_id' => $validated['pmb_year_id'],
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'registration_fee' => $validated['registration_fee'],
            'status' => $validated['status'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Gelombang pendaftaran berhasil ditambahkan.');
    }

    public function updateAdmissionWave(Request $request, AdmissionWave $admissionWave)
    {
        $validated = $request->validate([
            'pmb_year_id' => ['required', 'exists:pmb_years,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('admission_waves', 'code')
                    ->where(fn ($query) => $query->where('pmb_year_id', $request->pmb_year_id))
                    ->ignore($admissionWave->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'registration_fee' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:draft,open,closed'],
        ]);

        if ($request->boolean('is_active')) {
            AdmissionWave::where('pmb_year_id', $validated['pmb_year_id'])
                ->where('id', '!=', $admissionWave->id)
                ->update(['is_active' => false]);
        }

        $admissionWave->update([
            'pmb_year_id' => $validated['pmb_year_id'],
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'registration_fee' => $validated['registration_fee'],
            'status' => $validated['status'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Gelombang pendaftaran berhasil diperbarui.');
    }

    public function toggleAdmissionWave(AdmissionWave $admissionWave)
    {
        if (! $admissionWave->is_active) {
            AdmissionWave::where('pmb_year_id', $admissionWave->pmb_year_id)
                ->update(['is_active' => false]);

            $admissionWave->update([
                'is_active' => true,
                'status' => 'open',
            ]);
        } else {
            $admissionWave->update([
                'is_active' => false,
            ]);
        }

        return back()->with('success', 'Status aktif gelombang berhasil diubah.');
    }

    public function storeRegistrationPath(Request $request)
    {
        $validated = $this->validateRegistrationPath($request);

        RegistrationPath::create($validated + [
            'code' => RegistrationPath::makeCode($validated['name']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Jalur pendaftaran berhasil ditambahkan.');
    }

    public function updateRegistrationPath(Request $request, RegistrationPath $registrationPath)
    {
        $validated = $this->validateRegistrationPath($request, $registrationPath);

        if (! $request->boolean('is_active') && $this->isLastActivePath($registrationPath)) {
            return back()->withErrors('Minimal harus ada satu jalur pendaftaran yang aktif.');
        }

        // Kode tidak diubah karena tersimpan di data camaba dan link ?jalur= landing page.
        $registrationPath->update($validated + [
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Jalur pendaftaran berhasil diperbarui.');
    }

    public function toggleRegistrationPath(RegistrationPath $registrationPath)
    {
        if ($this->isLastActivePath($registrationPath)) {
            return back()->withErrors('Minimal harus ada satu jalur pendaftaran yang aktif.');
        }

        $registrationPath->update([
            'is_active' => ! $registrationPath->is_active,
        ]);

        return back()->with('success', 'Status jalur pendaftaran berhasil diubah.');
    }

    public function destroyRegistrationPath(RegistrationPath $registrationPath)
    {
        if ($registrationPath->applicants()->withTrashed()->exists()) {
            return back()->withErrors('Jalur ' . $registrationPath->name . ' sudah dipakai camaba sehingga tidak dapat dihapus. Nonaktifkan saja agar tidak tampil di form registrasi.');
        }

        if ($this->isLastActivePath($registrationPath)) {
            return back()->withErrors('Minimal harus ada satu jalur pendaftaran yang aktif.');
        }

        $registrationPath->delete();

        return back()->with('success', 'Jalur ' . $registrationPath->name . ' berhasil dihapus.');
    }

    private function validateRegistrationPath(Request $request, ?RegistrationPath $registrationPath = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('registration_paths', 'name')->ignore($registrationPath?->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
        ], [
            'name.unique' => 'Nama jalur ini sudah ada.',
        ], [
            'name' => 'nama jalur',
            'description' => 'keterangan',
            'sort_order' => 'urutan',
        ]);
    }

    private function isLastActivePath(RegistrationPath $registrationPath): bool
    {
        return $registrationPath->is_active
            && ! RegistrationPath::where('is_active', true)->whereKeyNot($registrationPath->id)->exists();
    }

    public function storeClassType(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:class_types,code'],
            'registration_code' => ['required', 'alpha', 'size:1', Rule::unique('class_types', 'registration_code')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'schedule' => ['nullable', 'string', 'max:255'],
            'scheme' => ['nullable', 'string', 'max:2000'],
        ]);

        ClassType::create([
            'code' => strtoupper($validated['code']),
            'registration_code' => strtoupper($validated['registration_code']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'schedule' => $validated['schedule'] ?? null,
            'scheme' => $validated['scheme'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Jenis kelas berhasil ditambahkan.');
    }

    public function updateClassType(Request $request, ClassType $classType)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:class_types,code,' . $classType->id],
            'registration_code' => ['required', 'alpha', 'size:1', Rule::unique('class_types', 'registration_code')->ignore($classType->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'schedule' => ['nullable', 'string', 'max:255'],
            'scheme' => ['nullable', 'string', 'max:2000'],
        ]);

        $classType->update([
            'code' => strtoupper($validated['code']),
            'registration_code' => strtoupper($validated['registration_code']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'schedule' => $validated['schedule'] ?? null,
            'scheme' => $validated['scheme'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Jenis kelas berhasil diperbarui.');
    }

    public function toggleClassType(ClassType $classType)
    {
        $classType->update([
            'is_active' => ! $classType->is_active,
        ]);

        return back()->with('success', 'Status jenis kelas berhasil diubah.');
    }

    public function storeDocumentType(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:document_types,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'allowed_extensions' => ['nullable', 'string', 'max:255'],
            'max_size_kb' => ['required', 'integer', 'min:1'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        DocumentType::create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_required' => $request->boolean('is_required'),
            'allowed_extensions' => $this->parseExtensions($validated['allowed_extensions'] ?? null),
            'max_size_kb' => $validated['max_size_kb'],
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Jenis berkas berhasil ditambahkan.');
    }

    public function updateDocumentType(Request $request, DocumentType $documentType)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:document_types,code,' . $documentType->id],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'allowed_extensions' => ['nullable', 'string', 'max:255'],
            'max_size_kb' => ['required', 'integer', 'min:1'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $documentType->update([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_required' => $request->boolean('is_required'),
            'allowed_extensions' => $this->parseExtensions($validated['allowed_extensions'] ?? null),
            'max_size_kb' => $validated['max_size_kb'],
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Jenis berkas berhasil diperbarui.');
    }

    public function toggleDocumentType(DocumentType $documentType)
    {
        $documentType->update([
            'is_active' => ! $documentType->is_active,
        ]);

        return back()->with('success', 'Status jenis berkas berhasil diubah.');
    }

    private function parseExtensions(?string $extensions): array
    {
        if (! $extensions) {
            return [];
        }

        return collect(explode(',', strtolower($extensions)))
            ->map(fn ($extension) => trim($extension))
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }
}
