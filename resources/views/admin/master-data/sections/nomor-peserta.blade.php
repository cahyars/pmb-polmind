{{-- Nomor urut peserta seleksi per gelombang, format {gelombang}.{urutan} --}}
@if($waves->isEmpty())
    <div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center">
        <p class="font-semibold text-slate-800">Belum ada gelombang pada {{ $selectedPmbYear?->name ?? 'Tahun PMB ini' }}.</p>
    </div>
@else
    @include('admin.master-data.sections._wave-tabs')

    @php
        $example = \App\Services\SelectionNumber::format($selectedWave, 1);
        $unpaidNumbered = $numbered->where('payment_status', '!=', 'valid');
    @endphp

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Format nomor</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-polmind-blue">{{ $example }}</p>
            <p class="mt-1 text-xs leading-5 text-slate-500">
                {{ \App\Services\RegistrationNumber::waveDigit($selectedWave) }} = kode {{ $selectedWave->name }}, 001 = urutan peserta yang sudah bayar formulir, dari pendaftar paling awal.
            </p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Sudah bernomor</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-slate-900">{{ $numbered->count() }}</p>
            <p class="mt-1 text-xs text-slate-500">Nomor tidak berubah walau ada camaba baru.</p>
        </div>
        <div class="rounded-xl border p-5 shadow-sm {{ $unnumbered->isNotEmpty() ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-white' }}">
            <p class="text-sm font-semibold text-slate-500">Sudah bayar, belum bernomor</p>
            <p class="mt-2 text-2xl font-bold tabular-nums {{ $unnumbered->isNotEmpty() ? 'text-amber-700' : 'text-slate-900' }}">{{ $unnumbered->count() }}</p>
            <p class="mt-1 text-xs text-slate-500">Klik "Beri Nomor" agar tercetak di kartu peserta.</p>
        </div>
    </div>

    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 lg:flex-row lg:items-center">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Peserta Seleksi {{ $selectedWave->name }}</h2>
                <p class="mt-1 text-sm leading-6 text-slate-600">
                    Beri Nomor menambahkan nomor lanjutan untuk yang baru bayar. Susun Ulang mengurutkan semua nomor dari {{ $example }}; lakukan sebelum kartu dicetak.
                </p>
            </div>

            <div class="flex shrink-0 flex-wrap gap-2">
                <form action="{{ route('admin.master-data.selection-numbers.assign', $selectedWave) }}" method="POST">
                    @csrf
                    <button type="submit" @disabled($unnumbered->isEmpty())
                            class="rounded-lg bg-polmind-blue px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-50">
                        Beri Nomor ({{ $unnumbered->count() }})
                    </button>
                </form>

                <form action="{{ route('admin.master-data.selection-numbers.renumber', $selectedWave) }}" method="POST"
                      x-data
                      @submit.prevent="window.Swal.fire({
                          icon: 'warning',
                          title: 'Susun ulang nomor peserta?',
                          text: @js('Semua nomor ' . $selectedWave->name . ' diurutkan ulang dari ' . $example . ' sesuai urutan mendaftar. Kartu peserta yang sudah dicetak bisa tidak sesuai lagi.'),
                          showCancelButton: true,
                          confirmButtonText: 'Ya, susun ulang',
                          cancelButtonText: 'Batal',
                          confirmButtonColor: '#003B82',
                      }).then((result) => result.isConfirmed && $el.submit())">
                    @csrf
                    <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Susun Ulang
                    </button>
                </form>

                <a href="{{ route('admin.selections.cards', ['wave' => $selectedWave->id]) }}" target="_blank"
                   class="inline-flex items-center gap-2 rounded-lg bg-polmind-yellow px-4 py-2.5 text-sm font-bold text-polmind-blue-dark hover:brightness-95">
                    <x-icon name="printer" class="h-4 w-4" stroke-width="2" />
                    Cetak Kartu Peserta
                </a>
            </div>
        </div>

        @if($unpaidNumbered->isNotEmpty())
            <p class="border-b border-red-100 bg-red-50 px-6 py-3 text-sm text-red-700">
                {{ $unpaidNumbered->count() }} camaba bernomor tetapi pembayarannya tidak lagi valid. Susun Ulang untuk melepas nomornya.
            </p>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="border-b border-slate-200 text-xs text-slate-400 [&_th]:px-5 [&_th]:py-3 [&_th]:font-medium">
                    <tr>
                        <th class="w-28">No. Peserta</th>
                        <th>Camaba</th>
                        <th>Program Studi</th>
                        <th>Tanggal Daftar</th>
                    </tr>
                </thead>
                <tbody class="table-zebra">
                    @forelse($numbered->concat($unnumbered) as $applicant)
                        <tr>
                            <td class="px-5 py-3">
                                @if($applicant->selection_sequence)
                                    <span class="text-base font-bold tabular-nums text-polmind-blue">{{ $applicant->selection_number }}</span>
                                @else
                                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800">Belum</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.applicants.show', $applicant->registration_number) }}" class="font-semibold text-slate-800 hover:text-polmind-blue">{{ $applicant->full_name }}</a>
                                <span class="block text-xs text-slate-500">{{ $applicant->registration_number }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $applicant->studyProgram ? $applicant->studyProgram->degree . ' - ' . $applicant->studyProgram->name : '-' }}</td>
                            <td class="px-5 py-3 tabular-nums text-slate-600">{{ $applicant->created_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada camaba {{ $selectedWave->name }} yang sudah bayar formulir.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endif
