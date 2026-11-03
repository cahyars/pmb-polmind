{{-- Pilihan gelombang dan pesan untuk halaman Seleksi (SPMB); gelombang dipilih lewat ?gelombang=. --}}
@php
    $waveStatus = [
        'open' => ['Dibuka', 'bg-green-100 text-green-700'],
        'upcoming' => ['Akan Datang', 'bg-yellow-100 text-yellow-800'],
        'closed' => ['Ditutup', 'bg-slate-100 text-slate-600'],
    ];
@endphp

@if($waves->isNotEmpty())
    <div class="flex flex-wrap gap-2" role="tablist" aria-label="Pilih gelombang">
        @foreach($waves as $wave)
            @php [$statusLabel, $statusClasses] = $waveStatus[$wave->publicStatus($currentWave)]; @endphp
            <a href="{{ route(request()->route()->getName(), ['gelombang' => $wave->id]) }}"
               role="tab"
               aria-selected="{{ $selectedWave?->is($wave) ? 'true' : 'false' }}"
               class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm transition
               {{ $selectedWave?->is($wave) ? 'border-polmind-blue bg-blue-50 font-semibold text-polmind-blue ring-2 ring-blue-100' : 'border-slate-200 bg-white font-medium text-slate-600 hover:border-slate-300' }}">
                {{ $wave->name }}
                <span class="rounded px-1.5 py-0.5 text-[10px] font-semibold {{ $statusClasses }}">{{ $statusLabel }}</span>
            </a>
        @endforeach
    </div>
@endif

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

@if(! $selectedWave)
    <div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center">
        <p class="font-semibold text-slate-800">Belum ada gelombang pada {{ $selectedPmbYear?->name ?? 'Tahun PMB ini' }}.</p>
    </div>
@endif
