{{-- Tabel rekap seleksi: $rows berisi registrants, ready, accepted, reserve, rejected, waiting, acceptance_rate, average_score --}}
@php
    $compact = $compact ?? false;
    $cell = 'px-3 py-2.5 text-right';
@endphp

<div class="mt-5 overflow-x-auto">
    <table class="w-full {{ $compact ? 'min-w-[520px]' : 'min-w-[760px]' }} text-left text-sm">
        <thead class="border-b border-slate-200 text-xs text-slate-400 [&_th]:whitespace-nowrap [&_th]:px-3 [&_th]:py-3 [&_th]:font-medium">
            <tr>
                <th>{{ $title }}</th>
                @unless($compact)<th class="text-right">Pendaftar</th>@endunless
                <th class="text-right">Siap Seleksi</th>
                <th class="text-right">Diterima</th>
                <th class="text-right">Cadangan</th>
                <th class="text-right">Ditolak</th>
                <th class="text-right">Menunggu</th>
                <th class="text-right">Rasio Diterima</th>
                @unless($compact)<th class="text-right">Rata-rata Nilai</th>@endunless
            </tr>
        </thead>
        <tbody class="table-zebra">
            @forelse($rows as $row)
                <tr>
                    <td class="px-3 py-2.5 font-semibold text-slate-900">{{ $name($row) }}</td>
                    @unless($compact)<td class="{{ $cell }}">{{ $row['registrants'] }}</td>@endunless
                    <td class="{{ $cell }}">{{ $row['ready'] }}</td>
                    <td class="{{ $cell }} font-semibold text-slate-900">{{ $row['accepted'] }}</td>
                    <td class="{{ $cell }}">{{ $row['reserve'] }}</td>
                    <td class="{{ $cell }}">{{ $row['rejected'] }}</td>
                    <td class="{{ $cell }}">{{ $row['waiting'] }}</td>
                    <td class="{{ $cell }}">{{ $row['acceptance_rate'] !== null ? $row['acceptance_rate'] . '%' : '-' }}</td>
                    @unless($compact)<td class="{{ $cell }}">{{ $row['average_score'] ?? '-' }}</td>@endunless
                </tr>
            @empty
                <tr><td colspan="9" class="px-3 py-8 text-center text-slate-500">Belum ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
