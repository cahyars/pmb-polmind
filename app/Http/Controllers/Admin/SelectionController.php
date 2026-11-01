<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SelectAdminPmbYear;
use App\Models\AchievementLevel;
use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\FeeComponent;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PmbYear;
use App\Models\ReRegistration;
use App\Models\Selection;
use App\Models\SelectionComponent;
use App\Models\SelectionScore;
use App\Models\StudyProgram;
use App\Services\SelectionNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SelectionController extends Controller
{
    // Keputusan yang dapat dipilih admin => status seleksi camaba.
    private const DECISIONS = [
        'diterima_1' => 'diterima',
        'diterima_2' => 'diterima',
        'cadangan' => 'cadangan',
        'ditolak' => 'ditolak',
    ];

    private const NOT_READY_MESSAGE = 'Camaba belum siap diseleksi. Pastikan berkas dan pembayaran pendaftaran sudah valid.';

    /**
     * Cetak Kartu Peserta Seleksi: 10 kartu per lembar A4, urut nomor peserta.
     */
    public function cards(Request $request)
    {
        $validated = $request->validate([
            'wave' => ['nullable', 'integer'],
            'study_program' => ['nullable', 'integer'],
            'from' => ['nullable', 'integer', 'min:1'],
            'to' => ['nullable', 'integer', 'min:1'],
        ]);

        $pmbYear = PmbYear::find($request->session()->get(SelectAdminPmbYear::SESSION_KEY));
        $waves = AdmissionWave::orderBy('start_date')->orderBy('id')->get();
        $wave = $waves->firstWhere('id', (int) ($validated['wave'] ?? 0))
            ?? $waves->firstWhere('id', AdmissionWave::current($pmbYear)?->id)
            ?? $waves->last();

        $applicants = $wave
            ? Applicant::with(['studyProgram', 'admissionWave', 'pmbYear'])
                ->where('admission_wave_id', $wave->id)
                ->whereNotNull('selection_sequence')
                ->when($validated['study_program'] ?? null, fn ($query, $programId) => $query->where('study_program_id', $programId))
                ->when($validated['from'] ?? null, fn ($query, $from) => $query->where('selection_sequence', '>=', $from))
                ->when($validated['to'] ?? null, fn ($query, $to) => $query->where('selection_sequence', '<=', $to))
                ->orderBy('selection_sequence')
                ->get()
            : collect();

        return view('print.kartu-peserta', [
            'applicants' => $applicants,
            'wave' => $wave,
            'waves' => $waves,
            'pmbYear' => $wave?->pmbYear ?? $pmbYear,
            'studyPrograms' => StudyProgram::orderBy('code')->get(),
            'filters' => $validated,
            'unnumbered' => $wave ? SelectionNumber::eligible($wave)->whereNull('selection_sequence')->count() : 0,
        ]);
    }

    public function index(Request $request)
    {
        $readySelection = Applicant::where('document_status', 'valid')
            ->where('payment_status', 'valid')
            ->count();

        $acceptedApplicants = Applicant::where('selection_status', 'diterima')->count();
        $reserveApplicants = Applicant::where('selection_status', 'cadangan')->count();
        $rejectedApplicants = Applicant::where('selection_status', 'ditolak')->count();

        $studyPrograms = StudyProgram::where('is_active', true)
            ->orderBy('code')
            ->get();

        $applicants = Applicant::query()
            ->with([
                'admissionWave',
                'studyProgram',
                'secondStudyProgram',
                'acceptedStudyProgram',
                'classType',
                'education',
                'selection',
                'reRegistrationInvoice',
            ])
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = $request->keyword;

                $query->where(function ($q) use ($keyword) {
                    $q->where('registration_number', 'like', "%{$keyword}%")
                        ->orWhere('full_name', 'like', "%{$keyword}%")
                        ->orWhere('nik', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%")
                        ->orWhereHas('education', function ($educationQuery) use ($keyword) {
                            $educationQuery->where('school_name', 'like', "%{$keyword}%")
                                ->orWhere('school_npsn', 'like', "%{$keyword}%");
                        });
                });
            })
            ->when($request->filled('study_program'), function ($query) use ($request) {
                $query->where('study_program_id', $request->study_program);
            })
            ->when($request->filled('registration_path'), function ($query) use ($request) {
                $query->where('registration_path', $request->registration_path);
            })
            ->when($request->filled('ready_status'), function ($query) use ($request) {
                if ($request->ready_status === 'ready') {
                    $query->where('document_status', 'valid')
                        ->where('payment_status', 'valid');
                }

                if ($request->ready_status === 'not_ready') {
                    $query->where(function ($q) {
                        $q->where('document_status', '!=', 'valid')
                            ->orWhere('payment_status', '!=', 'valid');
                    });
                }
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('selection_status', $request->status);
            })
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('admin.selections.index', compact(
            'readySelection',
            'acceptedApplicants',
            'reserveApplicants',
            'rejectedApplicants',
            'studyPrograms',
            'applicants'
        ));
    }

    public function show(string $registrationNumber)
    {
        $applicant = Applicant::where('registration_number', $registrationNumber)
            ->with([
                'studyProgram',
                'secondStudyProgram',
                'acceptedStudyProgram',
                'classType',
                'admissionWave',
                'education',
                'photoDocument',
                'selection.scores',
                'reRegistrationInvoice',
                'achievementLevel',
            ])
            ->firstOrFail();

        $components = $this->componentsFor($applicant);
        $scores = $applicant->selection?->scores->pluck('score', 'selection_component_id') ?? collect();

        // Pilihan kategori prestasi (jalur Prestasi) beserta SPI bila tanpa kategori.
        $achievementLevels = $applicant->registration_path === 'prestasi'
            ? AchievementLevel::ordered()->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $applicant->achievement_level_id))->get()
            : collect();
        $spiWithoutLevel = FeeComponent::with(['pathAmounts', 'waveAmounts'])
            ->where('code', FeeComponent::SPI_CODE)
            ->where('is_active', true)
            ->first()
            ?->amountFor($applicant->registration_path, $applicant->admission_wave_id);

        return view('admin.selections.show', compact('applicant', 'components', 'scores', 'achievementLevels', 'spiWithoutLevel'));
    }

    public function saveScores(Request $request, Applicant $applicant)
    {
        if (! $this->isReady($applicant)) {
            return back()->withErrors(self::NOT_READY_MESSAGE);
        }

        $components = $this->componentsFor($applicant);
        $scores = $this->validateScores($request, $components);

        DB::transaction(fn () => $this->storeScores($applicant, $components, $scores));

        return back()->with('success', 'Nilai seleksi berhasil disimpan.');
    }

    public function decide(Request $request, Applicant $applicant)
    {
        // Camaba jalur Prestasi yang diterima wajib diberi kategori prestasi (atau "tanpa kategori").
        $needsAchievementLevel = $applicant->registration_path === 'prestasi'
            && (self::DECISIONS[$request->input('decision')] ?? null) === 'diterima';

        $validated = $request->validate([
            'decision' => ['required', Rule::in(array_keys(self::DECISIONS))],
            'note' => [Rule::requiredIf($request->input('decision') === 'ditolak'), 'nullable', 'string', 'max:1000'],
            'achievement_level' => [
                Rule::requiredIf($needsAchievementLevel),
                'nullable',
                Rule::in(['none', ...AchievementLevel::where('is_active', true)->pluck('id')->map(fn ($id) => (string) $id), (string) $applicant->achievement_level_id]),
            ],
        ], [
            'decision.required' => 'Pilih keputusan seleksi terlebih dahulu.',
            'note.required' => 'Catatan wajib diisi jika camaba ditolak.',
            'achievement_level.required' => 'Pilih kategori prestasi untuk menentukan nominal SPI camaba jalur Prestasi.',
            'achievement_level.in' => 'Kategori prestasi tidak valid.',
        ]);

        $decision = $validated['decision'];
        $status = self::DECISIONS[$decision];
        $achievementLevelId = $needsAchievementLevel && $validated['achievement_level'] !== 'none'
            ? (int) $validated['achievement_level']
            : null;

        $acceptedProgramId = match ($decision) {
            'diterima_1' => $applicant->study_program_id,
            'diterima_2' => $applicant->second_study_program_id,
            default => null,
        };

        if ($decision === 'diterima_2' && ! $acceptedProgramId) {
            return back()->withErrors('Camaba tidak memiliki pilihan prodi kedua.');
        }

        if ($message = $this->guardSelectionDecision($applicant, $status, $acceptedProgramId)) {
            return back()->withErrors($message);
        }

        if ($achievementLevelId !== $applicant->achievement_level_id && $this->invoiceLocked($applicant->reRegistrationInvoice)) {
            return back()->withErrors('Kategori prestasi tidak dapat diubah karena camaba sudah melakukan pembayaran daftar ulang.');
        }

        $components = $this->componentsFor($applicant);
        $scores = $this->validateScores($request, $components);

        DB::transaction(function () use ($applicant, $components, $scores, $status, $acceptedProgramId, $achievementLevelId, $validated) {
            $selection = $this->storeScores($applicant, $components, $scores);

            $selection->update([
                'status' => $status,
                'note' => $validated['note'] ?? null,
                'decided_at' => now(),
                'decided_by_name' => $this->actorName(),
            ]);

            $applicant->update([
                'selection_status' => $status,
                'accepted_study_program_id' => $acceptedProgramId,
                'achievement_level_id' => $achievementLevelId,
                // Daftar ulang yang sudah dibayar tidak dimulai ulang saat keputusan disimpan lagi.
                ...($this->invoiceLocked($applicant->reRegistrationInvoice) ? [] : [
                    're_registration_status' => 'belum_daftar_ulang',
                    'sync_status' => 'belum_siap',
                ]),
            ]);

            if ($status === 'diterima') {
                $this->createReRegistrationInvoice($applicant);
            } else {
                $this->cancelReRegistrationInvoice($applicant);
            }
        });

        return back()->with('success', match ($decision) {
            'diterima_1', 'diterima_2' => 'Camaba diterima di pilihan ' . substr($decision, -1) . ' ('
                . StudyProgram::whereKey($acceptedProgramId)->value('code') . ') dan invoice daftar ulang dibuat.',
            'cadangan' => 'Camaba berhasil ditandai sebagai cadangan.',
            default => 'Camaba berhasil ditolak.',
        });
    }

    private function isReady(Applicant $applicant): bool
    {
        return $applicant->document_status === 'valid' && $applicant->payment_status === 'valid';
    }

    private function guardSelectionDecision(Applicant $applicant, string $targetStatus, $acceptedProgramId): ?string
    {
        $applicant->loadMissing('reRegistrationInvoice');

        if (! $this->isReady($applicant)) {
            return self::NOT_READY_MESSAGE;
        }

        $reRegistrationInvoice = $applicant->reRegistrationInvoice;

        $hasReRegistrationActivity = filled($applicant->nim) || $this->invoiceLocked($reRegistrationInvoice);

        if ($hasReRegistrationActivity && $targetStatus !== 'diterima') {
            return 'Status seleksi tidak dapat diubah karena camaba sudah memiliki aktivitas pembayaran daftar ulang.';
        }

        if (
            $hasReRegistrationActivity
            && $applicant->selection_status === 'diterima'
            && (int) $applicant->enrolled_study_program?->id !== (int) $acceptedProgramId
        ) {
            return 'Prodi tempat camaba diterima tidak dapat diubah karena camaba sudah melakukan pembayaran daftar ulang atau memiliki NIM.';
        }

        return null;
    }

    /**
     * Komponen tes aktif, ditambah komponen nonaktif yang sudah terlanjur dinilai untuk camaba ini.
     */
    private function componentsFor(Applicant $applicant): Collection
    {
        $scoredIds = SelectionScore::whereHas('selection', fn ($query) => $query->where('applicant_id', $applicant->id))
            ->pluck('selection_component_id');

        return SelectionComponent::where('is_active', true)
            ->orWhereIn('id', $scoredIds)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, string|null> nilai per id komponen
     */
    private function validateScores(Request $request, Collection $components): array
    {
        $rules = ['scores' => ['nullable', 'array']];
        $attributes = [];

        foreach ($components as $component) {
            $rules["scores.{$component->id}"] = ['nullable', 'numeric', 'min:0', 'max:100'];
            $attributes["scores.{$component->id}"] = 'nilai ' . $component->name;
        }

        $validated = $request->validate($rules, [], $attributes);

        return $components->mapWithKeys(fn ($component) => [
            $component->id => $validated['scores'][$component->id] ?? null,
        ])->all();
    }

    private function storeScores(Applicant $applicant, Collection $components, array $scores): Selection
    {
        $selection = Selection::firstOrCreate(['applicant_id' => $applicant->id]);

        foreach ($components as $component) {
            $score = $scores[$component->id] ?? null;

            if ($score === null || $score === '') {
                $selection->scores()->where('selection_component_id', $component->id)->delete();

                continue;
            }

            $selection->scores()->updateOrCreate(
                ['selection_component_id' => $component->id],
                ['score' => $score]
            );
        }

        $selection->update([
            'final_score' => Selection::weightedScore($selection->scores()->with('component')->get()),
        ]);

        return $selection;
    }

    /**
     * Tagihan daftar ulang sudah ada pembayarannya, sehingga nominalnya tidak boleh berubah lagi.
     */
    private function invoiceLocked(?Invoice $invoice): bool
    {
        return $invoice && in_array($invoice->status, ['waiting_verification', 'partial', 'paid'], true);
    }

    /**
     * Batas bayar daftar ulang: akhir periode daftar ulang gelombang camaba, atau 14 hari
     * bila periode itu belum diisi atau sudah lewat.
     */
    private function reRegistrationDueDate(Applicant $applicant): string
    {
        $end = $applicant->admissionWave?->re_registration_end_date;

        return ($end && $end->gte(today()) ? $end : now()->addDays(14))->toDateString();
    }

    private function createReRegistrationInvoice(Applicant $applicant): void
    {
        $applicant->load(['achievementLevel', 'admissionWave']);

        $fees = FeeComponent::with(['pathAmounts', 'waveAmounts'])
            ->where('type', 're_registration')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Nominal per jalur, per gelombang pendaftaran, atau per kategori prestasi (SPI).
        $prices = $fees->mapWithKeys(fn (FeeComponent $fee) => [$fee->id => $fee->priceFor($applicant)]);
        $totalAmount = $prices->sum(fn ($price) => (float) $price['amount']);
        $dueDate = $this->reRegistrationDueDate($applicant);

        $invoice = Invoice::where('applicant_id', $applicant->id)
            ->where('type', 're_registration')
            ->first();

        if ($this->invoiceLocked($invoice)) {
            return;
        }

        if (! $invoice) {
            $invoice = Invoice::create([
                'applicant_id' => $applicant->id,
                'invoice_number' => 'INV/DU/' . now()->format('Y') . '/' . $applicant->registration_number,
                'type' => 're_registration',
                'issue_date' => now()->toDateString(),
                'due_date' => $dueDate,
                'total_amount' => $totalAmount,
                'status' => 'unpaid',
                'note' => 'Invoice daftar ulang otomatis setelah camaba dinyatakan diterima.',
            ]);
        } else {
            // Nominal bisa berubah, jadwal cicilan lama tidak lagi cocok.
            $invoice->installments()->delete();

            $invoice->update([
                'installment_enabled_at' => null,
                'installment_enabled_by_name' => null,
                'invoice_number' => $invoice->invoice_number ?: 'INV/DU/' . now()->format('Y') . '/' . $applicant->registration_number,
                'issue_date' => now()->toDateString(),
                'due_date' => $dueDate,
                'total_amount' => $totalAmount,
                'status' => 'unpaid',
                'note' => 'Invoice daftar ulang otomatis setelah camaba dinyatakan diterima.',
            ]);

            // Komponen yang sudah dinonaktifkan di Master Data tidak ditagihkan lagi.
            $invoice->items()->whereNotIn('fee_component_id', $fees->pluck('id'))->get()->each->delete();
        }

        foreach ($fees as $fee) {
            InvoiceItem::updateOrCreate(
                [
                    'invoice_id' => $invoice->id,
                    'fee_component_id' => $fee->id,
                ],
                [
                    'name' => $prices[$fee->id]['name'],
                    'amount' => $prices[$fee->id]['amount'],
                    'quantity' => 1,
                    'subtotal' => $prices[$fee->id]['amount'],
                    'sort_order' => $fee->sort_order,
                ]
            );
        }

        ReRegistration::updateOrCreate(
            ['applicant_id' => $applicant->id],
            [
                'invoice_id' => $invoice->id,
                'status' => 'belum_daftar_ulang',
                'deadline_date' => $dueDate,
                'validated_at' => null,
                'validated_by_name' => null,
                'ready_sync_at' => null,
                'admin_note' => null,
            ]
        );
    }

    private function cancelReRegistrationInvoice(Applicant $applicant): void
    {
        $invoice = Invoice::where('applicant_id', $applicant->id)
            ->where('type', 're_registration')
            ->first();

        if ($invoice && ! in_array($invoice->status, ['waiting_verification', 'partial', 'paid'])) {
            $invoice->update([
                'status' => 'cancelled',
                'note' => 'Invoice daftar ulang dibatalkan karena status seleksi bukan diterima.',
            ]);
        }

        // Per model (bukan update massal) agar perubahan tercatat di Log Aktivitas.
        ReRegistration::where('applicant_id', $applicant->id)->get()->each->update([
            'status' => 'belum_daftar_ulang',
            'ready_sync_at' => null,
        ]);
    }
}