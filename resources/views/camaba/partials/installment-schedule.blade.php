{{-- Jadwal pembayaran daftar ulang; hanya ada untuk camaba yang diberi jadwal oleh admin. --}}
@php
    $scheduleRupiah = fn ($amount) => 'Rp' . number_format((float) $amount, 0, ',', '.');
    $scheduleStatus = [
        'lunas' => ['Lunas', 'bg-green-100 text-green-700'],
        'sebagian' => ['Sebagian', 'bg-yellow-100 text-yellow-700'],
        'belum' => ['Belum Dibayar', 'bg-slate-100 text-slate-600'],
    ];
@endphp

<div class="overflow-x-auto rounded-2xl border border-slate-200">
    <table class="w-full min-w-[520px] text-left text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-xs text-slate-500 [&_th]:font-bold">
            <tr>
                <th class="px-4 py-2.5">No</th>
                <th class="px-4 py-2.5">Komponen</th>
                <th class="px-4 py-2.5">Jatuh Tempo</th>
                <th class="px-4 py-2.5 text-right">Nominal</th>
                <th class="px-4 py-2.5">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
            @foreach($invoice->installmentSchedule() as $row)
                <tr>
                    <td class="px-4 py-2.5 text-slate-500">{{ $loop->iteration }}</td>
                    <td class="px-4 py-2.5 font-semibold text-slate-800">
                        {{ $row['installment']->name }}
                        <span class="block text-xs font-normal text-slate-500">Pembayaran ke-{{ $row['installment']->sequence }}</span>
                    </td>
                    <td class="px-4 py-2.5 {{ $row['overdue'] ? 'font-bold text-red-600' : 'text-slate-700' }}">
                        {{ $row['installment']->due_date->translatedFormat('d M Y') }}
                    </td>
                    <td class="px-4 py-2.5 text-right font-bold text-slate-900">{{ $scheduleRupiah($row['installment']->amount) }}</td>
                    <td class="px-4 py-2.5">
                        <span class="rounded-full px-2.5 py-1 text-xs font-black {{ $scheduleStatus[$row['status']][1] }}">{{ $scheduleStatus[$row['status']][0] }}</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
