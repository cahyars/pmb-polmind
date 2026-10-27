{{-- Jadwal Seleksi per gelombang --}}
@php
    $dateField = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100';
    $formatDate = fn ($date, $pattern = 'j M Y') => $date?->translatedFormat($pattern) ?? '-';
@endphp

@if($waves->isEmpty())
    <div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center">
        <p class="font-semibold text-slate-800">Belum ada gelombang pada {{ $selectedPmbYear?->name ?? 'Tahun PMB ini' }}.</p>
        <a href="{{ route('admin.master-data.show', 'gelombang') }}" class="mt-2 inline-block text-sm font-semibold text-polmind-blue underline">Tambah gelombang terlebih dahulu</a>
    </div>
@else
    @include('admin.master-data.sections._wave-tabs')

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
        {{-- Jadwal tes --}}
        <div class="min-w-0 rounded-xl border border-slate-200 bg-white shadow-sm" x-data="{ adding: {{ $errors->any() && old('_form') === 'schedule-create' ? 'true' : 'false' }} }">
            <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Jadwal Tes {{ $selectedWave->name }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Tampil di landing page dan halaman status seleksi camaba gelombang ini.
                    </p>
                </div>

                <button type="button" @click="adding = ! adding"
                        @disabled($selectionComponents->isEmpty())
                        class="rounded-lg bg-polmind-yellow px-5 py-3 text-sm font-semibold text-polmind-blue-dark shadow-sm transition hover:brightness-95 disabled:cursor-not-allowed disabled:opacity-50">
                    Tambah Jadwal
                </button>
            </div>

            @if($selectionComponents->isEmpty())
                <p class="border-b border-slate-200 bg-yellow-50 px-6 py-3 text-sm text-yellow-800">
                    Belum ada komponen tes aktif.
                    <a href="{{ route('admin.master-data.show', 'komponen-tes') }}" class="font-semibold underline">Tambah komponen tes</a> terlebih dahulu.
                </p>
            @endif

            <form x-show="adding" x-cloak action="{{ route('admin.master-data.selection-schedules.store') }}" method="POST" class="border-b border-slate-200 bg-slate-50 p-6">
                @csrf
                <input type="hidden" name="_form" value="schedule-create">
                <input type="hidden" name="admission_wave_id" value="{{ $selectedWave->id }}">

                @include('admin.master-data.sections._schedule-fields', ['schedule' => null])

                <div class="mt-4 flex justify-end gap-3">
                    <button type="button" @click="adding = false" class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">Batal</button>
                    <button type="submit" class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">Simpan Jadwal</button>
                </div>
            </form>

            <ul class="divide-y divide-slate-100">
                @forelse($selectedWave->selectionSchedules as $schedule)
                    <li x-data="{ editing: false }" class="p-5 sm:px-6">
                        <div x-show="! editing" class="flex flex-col gap-4 md:flex-row md:items-start">
                            <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-lg bg-blue-50 text-polmind-blue">
                                <span class="text-lg font-bold leading-none">{{ $schedule->test_date->format('d') }}</span>
                                <span class="mt-0.5 text-[11px] font-semibold uppercase">{{ $schedule->test_date->translatedFormat('M') }}</span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-slate-900">{{ $schedule->component?->name ?? '-' }}</p>
                                <p class="mt-0.5 text-sm text-slate-600">
                                    {{ $schedule->test_date->translatedFormat('l, j F Y') }}
                                    · {{ $schedule->time_range ?? 'Jam menyusul' }}
                                </p>
                                <dl class="mt-2 grid gap-x-6 gap-y-1 text-xs text-slate-500 sm:grid-cols-2">
                                    <div class="flex gap-1"><dt class="shrink-0">Lokasi:</dt><dd class="font-medium text-slate-700">{{ $schedule->location ?? 'Menyusul' }}</dd></div>
                                    <div class="flex gap-1"><dt class="shrink-0">PIC:</dt>
                                        <dd class="font-medium {{ $schedule->pic_name ? 'text-slate-700' : 'text-red-600' }}">{{ $schedule->pic_name ?? 'Belum ditentukan' }}</dd>
                                    </div>
                                    @if($schedule->notes)
                                        <div class="flex gap-1 sm:col-span-2"><dt class="shrink-0">Catatan:</dt><dd class="text-slate-700">{{ $schedule->notes }}</dd></div>
                                    @endif
                                </dl>
                            </div>

                            <div class="flex shrink-0 gap-2">
                                <button type="button" @click="editing = true"
                                        class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-polmind-blue">Edit</button>
                                <form action="{{ route('admin.master-data.selection-schedules.destroy', $schedule) }}" method="POST"
                                      onsubmit="return confirm('Hapus jadwal {{ $schedule->component?->name }} ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-600">Hapus</button>
                                </form>
                            </div>
                        </div>

                        <form x-show="editing" x-cloak action="{{ route('admin.master-data.selection-schedules.update', $schedule) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="admission_wave_id" value="{{ $selectedWave->id }}">

                            @include('admin.master-data.sections._schedule-fields', ['schedule' => $schedule])

                            <div class="mt-4 flex justify-end gap-3">
                                <button type="button" @click="editing = false" class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">Batal</button>
                                <button type="submit" class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">Update Jadwal</button>
                            </div>
                        </form>
                    </li>
                @empty
                    <li class="px-6 py-10 text-center text-sm text-slate-500">Belum ada jadwal tes untuk {{ $selectedWave->name }}.</li>
                @endforelse
            </ul>
        </div>

        {{-- Ringkasan gelombang, pengumuman & daftar ulang --}}
        <aside class="min-w-0 space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">{{ $selectedWave->name }}</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Pendaftaran</dt><dd class="text-right font-medium text-slate-800">{{ $formatDate($selectedWave->start_date) }} – {{ $formatDate($selectedWave->end_date) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Biaya formulir</dt><dd class="font-medium text-slate-800">Rp{{ number_format((float) $selectedWave->registration_fee, 0, ',', '.') }}</dd></div>
                </dl>
                <a href="{{ route('admin.master-data.show', 'gelombang') }}" class="mt-3 inline-block text-xs font-semibold text-polmind-blue underline">Ubah periode di menu Gelombang</a>
            </div>

            <form action="{{ route('admin.master-data.admission-waves.timeline', $selectedWave) }}" method="POST"
                  class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                @method('PUT')

                <h2 class="text-base font-semibold text-slate-900">Pengumuman & Daftar Ulang</h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <label for="announcement_date" class="text-xs font-semibold text-slate-600">Tanggal Pengumuman</label>
                        <input id="announcement_date" type="date" name="announcement_date" value="{{ old('announcement_date', $selectedWave->announcement_date?->format('Y-m-d')) }}" class="{{ $dateField }}">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="min-w-0">
                            <label for="re_registration_start_date" class="text-xs font-semibold text-slate-600">Daftar Ulang Mulai</label>
                            <input id="re_registration_start_date" type="date" name="re_registration_start_date" value="{{ old('re_registration_start_date', $selectedWave->re_registration_start_date?->format('Y-m-d')) }}" class="{{ $dateField }}">
                        </div>
                        <div class="min-w-0">
                            <label for="re_registration_end_date" class="text-xs font-semibold text-slate-600">Sampai</label>
                            <input id="re_registration_end_date" type="date" name="re_registration_end_date" value="{{ old('re_registration_end_date', $selectedWave->re_registration_end_date?->format('Y-m-d')) }}" class="{{ $dateField }}">
                        </div>
                    </div>
                </div>

                <button type="submit" class="mt-5 w-full rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">Simpan Tanggal</button>
            </form>
        </aside>
    </div>
@endif
