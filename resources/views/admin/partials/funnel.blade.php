{{-- Funnel horizontal: $stages = koleksi ['label' => ..., 'value' => ..., 'percent' => ...]. Tiap tahap trapesium dari tingginya sendiri ke tinggi tahap berikutnya. --}}
@php
    $stages = collect($stages)->values();
    $stageCount = max($stages->count(), 1);
    $funnelMax = max($stages->max('value'), 1);
    // Ramp biru ordinal (gelap ke terang), diambil merata sesuai jumlah tahap.
    $ramp = ['#104281', '#184f95', '#1c5cab', '#256abf', '#2a78d6', '#5598e7', '#6da7ec', '#86b6ef'];
    $stageColor = fn ($i) => $ramp[$stageCount > 1 ? (int) round($i * (count($ramp) - 1) / ($stageCount - 1)) : 0];
    [$svgWidth, $svgHeight, $gap] = [600, 220, 3];
    $columnWidth = $svgWidth / $stageCount;
    $stageHeight = fn ($value) => max($value / $funnelMax * $svgHeight, $svgHeight * 0.02);
    $formatNumber = fn ($value) => number_format($value, 0, ',', '.');
@endphp

<div class="overflow-x-auto">
    <div class="min-w-[560px]">
        <svg viewBox="0 0 {{ $svgWidth }} {{ $svgHeight }}" preserveAspectRatio="none" class="block h-56 w-full" role="img" aria-label="Funnel tahapan pendaftar">
            @foreach($stages as $i => $item)
                @php
                    $left = $stageHeight($item['value']);
                    $right = $stageHeight($stages[$i + 1]['value'] ?? $item['value']);
                    $x0 = $i * $columnWidth + ($i > 0 ? $gap / 2 : 0);
                    $x1 = ($i + 1) * $columnWidth - ($i < $stageCount - 1 ? $gap / 2 : 0);
                    $mid = $svgHeight / 2;
                @endphp
                <polygon points="{{ $x0 }},{{ $mid - $left / 2 }} {{ $x1 }},{{ $mid - $right / 2 }} {{ $x1 }},{{ $mid + $right / 2 }} {{ $x0 }},{{ $mid + $left / 2 }}"
                         fill="{{ $stageColor($i) }}" class="transition-opacity hover:opacity-80">
                    <title>{{ $item['label'] }}: {{ $formatNumber($item['value']) }} camaba ({{ $item['percent'] }}%)</title>
                </polygon>
            @endforeach
        </svg>
        <ol class="mt-4 grid gap-3" style="grid-template-columns: repeat({{ $stageCount }}, minmax(0, 1fr))">
            @foreach($stages as $i => $item)
                @php
                    $previous = $i > 0 ? $stages[$i - 1]['value'] : null;
                @endphp
                <li class="min-w-0">
                    <p class="flex min-h-[2rem] items-start gap-1.5 text-xs font-medium leading-tight text-slate-600">
                        <span class="mt-0.5 h-2.5 w-2.5 shrink-0 rounded-sm" style="background: {{ $stageColor($i) }}"></span>{{ $item['label'] }}
                    </p>
                    <p class="mt-1.5 text-lg font-semibold text-slate-900">{{ $formatNumber($item['value']) }}</p>
                    <p class="text-xs text-slate-500">{{ $item['percent'] }}% dari pendaftar</p>
                    @if($previous !== null)
                        <p class="mt-0.5 text-xs text-slate-400">{{ $previous > 0 ? round($item['value'] / $previous * 100) : 0 }}% dari tahap sebelumnya</p>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</div>
