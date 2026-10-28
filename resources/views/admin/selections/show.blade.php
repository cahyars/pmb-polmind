@extends('layouts.admin')

@section('title', 'Proses Seleksi')
@section('page_title', 'Proses Seleksi')
@section('page_subtitle', 'Input nilai per komponen tes dan tentukan keputusan seleksi.')

@section('content')
@php
    $selectionLabels = [
        'belum_diseleksi' => 'Belum Diseleksi',
        'diterima' => 'Diterima',
        'cadangan' => 'Cadangan',
        'ditolak' => 'Ditolak',
    ];

    $selectionStatus = $applicant->selection_status ?? 'belum_diseleksi';
    $acceptedChoice = $applicant->acceptedChoice();
    $isReady = $applicant->document_status === 'valid' && $applicant->payment_status === 'valid';

    $reRegistrationInvoice = $applicant->reRegistrationInvoice;
    $hasReRegistrationActivity = filled($applicant->nim) || ($reRegistrationInvoice
        && in_array($reRegistrationInvoice->status, ['waiting_verification', 'partial', 'paid']));

    $currentDecision = old('decision', match (true) {
        $acceptedChoice !== null => 'diterima_' . $acceptedChoice,
        in_array($selectionStatus, ['cadangan', 'ditolak'], true) => $selectionStatus,
        default => null,
    });

    $program = fn ($studyProgram) => $studyProgram ? $studyProgram->code . ' - ' . $studyProgram->degree . ' ' . $studyProgram->name : null;

    $decisions = [
        'diterima_1' => [
            'label' => 'Diterima di Pilihan 1',
            'desc' => $program($applicant->studyProgram) ?? '-',
            'available' => (bool) $applicant->studyProgram,
            'class' => 'peer-checked:border-green-600 peer-checked:bg-green-50',
        ],
        'diterima_2' => [
            'label' => 'Diterima di Pilihan 2',
            'desc' => $program($applicant->secondStudyProgram) ?? 'Camaba tidak memilih prodi kedua',
            'available' => (bool) $applicant->secondStudyProgram,
            'class' => 'peer-checked:border-green-600 peer-checked:bg-green-50',
        ],
        'cadangan' => [
            'label' => 'Cadangan',
            'desc' => 'Menunggu kuota. Invoice daftar ulang dibatalkan.',
            'available' => ! $hasReRegistrationActivity,
            'class' => 'peer-checked:border-yellow-500 peer-checked:bg-yellow-50',
        ],
        'ditolak' => [
            'label' => 'Tidak Lolos',
            'desc' => 'Catatan wajib diisi.',
            'available' => ! $hasReRegistrationActivity,
            'class' => 'peer-checked:border-red-600 peer-checked:bg-red-50',
        ],
    ];

    $scoreInputs = $components->mapWithKeys(fn ($component) => [
        $component->id => old('scores.' . $component->id, $scores->get($component->id) !== null ? (float) $scores->get($component->id) : ''),
    ]);
@endphp

<div class="space-y-8">

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-5 py-4 text-sm font-semibold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Header --}}
    <div class="rounded-xl bg-polmind-blue p-6 text-white shadow-lg shadow-blue-900/20">
        <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
            <div>
                <a href="{{ route('admin.selections.index') }}"
                   class="inline-flex items-center rounded-lg bg-white/10 px-4 py-2 text-xs font-semibold text-white transition hover:bg-white/20">
                    ← Kembali ke Daftar Seleksi
                </a>

                <div class="mt-6 flex items-center gap-5">
                    @if($applicant->photoDocument)
                        <button type="button"
                                @click="$store.preview.show(@js(route('admin.documents.file', $applicant->photoDocument)), @js(basename($applicant->photoDocument->file_path)))"
                                class="shrink-0" title="Lihat pas foto">
                            <img src="{{ route('admin.documents.file', $applicant->photoDocument) }}"
                                 alt="Foto {{ $applicant->full_name }}"
                                 class="h-24 w-20 rounded-lg bg-white object-cover ring-4 ring-white/20">
                        </button>
                    @endif

                    <div>
                        <p class="text-sm font-semibold text-blue-100">{{ $applicant->registration_number }}</p>
                        <h1 class="mt-2 text-2xl font-bold">{{ $applicant->full_name }}</h1>
                        <p class="mt-2 text-sm text-blue-100">
                            {{ $applicant->education?->school_name ?? '-' }} · {{ $applicant->registration_path_label }} · {{ $applicant->admissionWave?->name ?? '-' }}
                        </p>
                        <a href="{{ route('admin.applicants.show', $applicant->registration_number) }}"
                           class="mt-3 inline-flex text-xs font-semibold text-white underline underline-offset-4 hover:text-blue-100">
                            Lihat data lengkap camaba
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3 lg:w-[560px]">
                <div class="rounded-lg bg-white/10 p-4">
                    <p class="text-xs font-semibold text-blue-100">Pilihan 1</p>
                    <p class="mt-2 text-lg font-bold">{{ $applicant->studyProgram?->code ?? '-' }}</p>
                    <p class="text-xs text-blue-100">{{ $applicant->classType?->name ?? '-' }}</p>
                </div>

                <div class="rounded-lg bg-white/10 p-4">
                    <p class="text-xs font-semibold text-blue-100">Pilihan 2</p>
                    <p class="mt-2 text-lg font-bold">{{ $applicant->secondStudyProgram?->code ?? '-' }}</p>
                    <p class="text-xs text-blue-100">{{ $applicant->secondStudyProgram ? 'Pilihan cadangan camaba' : 'Tidak memilih' }}</p>
                </div>

                <div class="rounded-lg bg-white/10 p-4">
                    <p class="text-xs font-semibold text-blue-100">Status Seleksi</p>
                    <p class="mt-2 text-lg font-bold">{{ $selectionLabels[$selectionStatus] ?? $selectionStatus }}</p>
                    <p class="text-xs text-blue-100">
                        {{ $acceptedChoice ? 'Pilihan ' . $acceptedChoice . ' · ' . $applicant->enrolled_study_program?->code : ($applicant->selection?->decided_by_name ?? 'Belum diputuskan') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    @unless($isReady)
        <div class="rounded-lg border border-yellow-200 bg-yellow-50 px-5 py-4 text-sm font-semibold text-yellow-800">
            Camaba belum siap diseleksi. Berkas dan pembayaran pendaftaran harus berstatus valid.
        </div>
    @endunless

    <form method="POST"
          action="{{ route('admin.selections.decide', $applicant) }}"
          x-data="selectionForm(@js($components->map(fn ($component) => ['id' => $component->id, 'weight' => $component->weight])->values()), @js($scoreInputs))"
          class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
        @csrf
        @method('PATCH')

        {{-- Nilai --}}
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm" aria-labelledby="nilai-title">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 p-6">
                <div>
                    <h2 id="nilai-title" class="text-base font-semibold text-slate-900">Nilai Seleksi</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Sesuai komponen tes di Master Data. Kosongkan jika camaba tidak mengikuti tes tersebut.
                    </p>
                </div>
                <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">Internal</span>
            </div>

            <div class="space-y-4 p-6">
                @forelse($components as $component)
                    <div class="grid items-center gap-3 sm:grid-cols-[minmax(0,1fr)_8rem]">
                        <label for="score-{{ $component->id }}" class="min-w-0">
                            <span class="block font-semibold text-slate-800">
                                {{ $component->name }}
                                @unless($component->is_active)
                                    <span class="ml-1 text-[11px] font-bold text-slate-400">Nonaktif</span>
                                @endunless
                            </span>
                            <span class="mt-0.5 block text-xs text-slate-500">Bobot {{ $component->weight }}{{ $component->description ? ' · ' . $component->description : '' }}</span>
                        </label>
                        <input id="score-{{ $component->id }}"
                               type="number"
                               step="0.01"
                               min="0"
                               max="100"
                               name="scores[{{ $component->id }}]"
                               x-model="scores[{{ $component->id }}]"
                               value="{{ $scoreInputs[$component->id] }}"
                               placeholder="0-100"
                               @disabled(! $isReady)
                               class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-right text-sm font-semibold outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100 disabled:bg-slate-50">
                    </div>
                @empty
                    <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">
                        Belum ada komponen tes aktif. Tambahkan di
                        <a href="{{ route('admin.master-data.show', 'komponen-tes') }}" class="font-semibold text-polmind-blue underline">Master Data › Komponen Tes</a>.
                    </p>
                @endforelse

                <div class="flex items-center justify-between rounded-lg bg-blue-50 px-4 py-3">
                    <div>
                        <p class="text-sm font-semibold text-polmind-blue">Nilai Akhir</p>
                        <p class="text-xs text-slate-500">Rata-rata berbobot dari komponen yang terisi.</p>
                    </div>
                    <p class="text-2xl font-bold text-polmind-blue" x-text="finalScore">{{ $applicant->selection?->final_score ?? '-' }}</p>
                </div>

                @if($isReady && $components->isNotEmpty())
                    <div class="flex justify-end">
                        <button type="submit"
                                formaction="{{ route('admin.selections.scores', $applicant) }}"
                                formnovalidate
                                class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                            Simpan Nilai Saja
                        </button>
                    </div>
                @endif
            </div>
        </section>

        {{-- Keputusan --}}
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm" aria-labelledby="keputusan-title">
            <div class="border-b border-slate-200 p-6">
                <h2 id="keputusan-title" class="text-base font-semibold text-slate-900">Keputusan Seleksi</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Camaba hanya melihat status lolos atau tidak dan prodi tempat ia diterima, tanpa nilai.
                </p>
            </div>

            <div class="space-y-5 p-6">
                <fieldset class="grid gap-3 sm:grid-cols-2">
                    <legend class="sr-only">Keputusan</legend>
                    @foreach($decisions as $value => $decision)
                        @php $enabled = $isReady && $decision['available']; @endphp
                        <label class="{{ $enabled ? 'cursor-pointer' : 'cursor-not-allowed opacity-50' }}">
                            <input type="radio"
                                   name="decision"
                                   value="{{ $value }}"
                                   class="peer sr-only"
                                   @checked($currentDecision === $value)
                                   @disabled(! $enabled)>
                            <span class="block h-full rounded-lg border-2 border-slate-200 p-4 transition peer-focus-visible:ring-4 peer-focus-visible:ring-blue-100 {{ $decision['class'] }}">
                                <span class="block text-sm font-bold text-slate-900">{{ $decision['label'] }}</span>
                                <span class="mt-1 block text-xs leading-5 text-slate-500">{{ $decision['desc'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </fieldset>

                <div>
                    <label for="note" class="text-sm font-semibold text-slate-700">Catatan untuk camaba</label>
                    <p class="mt-1 text-xs text-slate-500">Tampil di halaman camaba. Jangan cantumkan nilai.</p>
                    <textarea id="note"
                              name="note"
                              rows="3"
                              maxlength="1000"
                              @disabled(! $isReady)
                              placeholder="Opsional, wajib diisi jika tidak lolos."
                              class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100 disabled:bg-slate-50">{{ old('note', $applicant->selection?->note) }}</textarea>
                </div>

                @if($hasReRegistrationActivity)
                    <p class="rounded-lg bg-yellow-50 p-3 text-xs leading-5 text-yellow-800">
                        Camaba sudah memiliki aktivitas pembayaran daftar ulang atau NIM, sehingga status dan prodi diterima tidak dapat diubah.
                    </p>
                @endif

                @if($isReady)
                    <button type="submit"
                            onclick="return confirm('Simpan keputusan seleksi? Jika diterima, invoice daftar ulang dibuat otomatis.')"
                            class="w-full rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                        Simpan Nilai &amp; Keputusan
                    </button>
                @endif
            </div>
        </section>
    </form>

</div>

<script>
    // Pratinjau nilai akhir: rata-rata berbobot komponen yang terisi, sama dengan perhitungan server.
    window.selectionForm = (components, scores) => ({
        scores,

        get finalScore() {
            let total = 0;
            let totalWeight = 0;

            components.forEach(({ id, weight }) => {
                const value = this.scores[id];

                if (value === '' || value === null || value === undefined || isNaN(parseFloat(value))) {
                    return;
                }

                total += parseFloat(value) * Math.max(weight, 0);
                totalWeight += Math.max(weight, 0);
            });

            return totalWeight > 0 ? (Math.round(total / totalWeight * 100) / 100).toFixed(2) : '-';
        },
    });
</script>
@endsection
