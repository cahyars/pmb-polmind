{{-- Pilihan gelombang (Tahun PMB terpilih) untuk halaman jadwal & PIC. --}}
@php
    $waveStatus = [
        'open' => ['Dibuka', 'bg-green-100 text-green-700'],
        'upcoming' => ['Akan Datang', 'bg-yellow-100 text-yellow-800'],
        'closed' => ['Ditutup', 'bg-slate-100 text-slate-600'],
    ];
@endphp

<div class="flex flex-wrap gap-2" role="tablist" aria-label="Pilih gelombang">
    @foreach($waves as $wave)
        @php [$statusLabel, $statusClasses] = $waveStatus[$wave->publicStatus($currentWave)]; @endphp
        <a href="{{ route('admin.master-data.show', [$section, 'gelombang' => $wave->id]) }}"
           role="tab"
           aria-selected="{{ $selectedWave->is($wave) ? 'true' : 'false' }}"
           class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm transition
           {{ $selectedWave->is($wave) ? 'border-polmind-blue bg-blue-50 font-semibold text-polmind-blue ring-2 ring-blue-100' : 'border-slate-200 bg-white font-medium text-slate-600 hover:border-slate-300' }}">
            {{ $wave->name }}
            <span class="rounded px-1.5 py-0.5 text-[10px] font-semibold {{ $statusClasses }}">{{ $statusLabel }}</span>
        </a>
    @endforeach
</div>
