{{-- Grafik Chart.js: $config = ['type' => 'bar'|'line', 'labels' => [...], 'datasets' => [['label' => ..., 'data' => [...], 'status' => null]], 'horizontal' => false, 'stacked' => false] --}}
@php
    $hasData = collect($config['datasets'])->flatMap(fn ($dataset) => $dataset['data'])->sum() > 0;
@endphp

@if($hasData)
    <div class="relative {{ $height ?? 'h-72' }}">
        <canvas data-chart="{{ json_encode($config) }}" role="img" aria-label="{{ $label }}"></canvas>
    </div>
@else
    <div class="flex {{ $height ?? 'h-72' }} items-center justify-center rounded-lg bg-slate-50 text-sm text-slate-500">
        Belum ada data untuk ditampilkan.
    </div>
@endif
