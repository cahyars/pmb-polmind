{{-- Penugasan PIC (nama dosen) untuk setiap jadwal tes --}}
@if($waves->isEmpty())
    <div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center">
        <p class="font-semibold text-slate-800">Belum ada gelombang pada {{ $selectedPmbYear?->name ?? 'Tahun PMB ini' }}.</p>
    </div>
@else
    @include('admin.master-data.sections._wave-tabs')

    @php
        $schedules = $selectedWave->selectionSchedules;
        $assigned = $schedules->filter(fn ($schedule) => filled($schedule->pic_name))->count();
    @endphp

    <form action="{{ route('admin.master-data.admission-waves.pics', $selectedWave) }}" method="POST"
          class="rounded-xl border border-slate-200 bg-white shadow-sm">
        @csrf
        @method('PUT')

        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
            <div>
                <h2 class="text-base font-semibold text-slate-900">PIC Tes {{ $selectedWave->name }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Isi nama dosen penanggung jawab tiap tes. Pisahkan dengan koma jika lebih dari satu dosen.
                </p>
            </div>

            <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $schedules->isNotEmpty() && $assigned === $schedules->count() ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-800' }}">
                {{ $assigned }} dari {{ $schedules->count() }} tes sudah ada PIC
            </span>
        </div>

        @if($schedules->isEmpty())
            <div class="px-6 py-10 text-center text-sm text-slate-500">
                Belum ada jadwal tes untuk {{ $selectedWave->name }}.
                <a href="{{ route('admin.master-data.show', ['jadwal-seleksi', 'gelombang' => $selectedWave->id]) }}" class="font-semibold text-polmind-blue underline">Atur jadwal seleksi</a>
            </div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach($schedules as $schedule)
                    <li class="grid gap-3 p-5 sm:px-6 md:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)] md:items-center">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900">{{ $schedule->component?->name ?? '-' }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ $schedule->test_date->translatedFormat('l, j F Y') }} · {{ $schedule->time_range ?? 'Jam menyusul' }}
                                @if($schedule->location) · {{ $schedule->location }} @endif
                            </p>
                        </div>

                        <div class="min-w-0">
                            <label for="pic-{{ $schedule->id }}" class="sr-only">PIC {{ $schedule->component?->name }}</label>
                            <input id="pic-{{ $schedule->id }}"
                                   type="text"
                                   name="pic_name[{{ $schedule->id }}]"
                                   value="{{ old('pic_name.' . $schedule->id, $schedule->pic_name) }}"
                                   maxlength="255"
                                   placeholder="Nama dosen, contoh: Budi Santoso, M.T."
                                   class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="flex justify-end border-t border-slate-200 p-5 sm:px-6">
                <button type="submit" class="rounded-lg bg-polmind-blue px-6 py-3 text-sm font-semibold text-white">Simpan PIC</button>
            </div>
        @endif
    </form>
@endif
