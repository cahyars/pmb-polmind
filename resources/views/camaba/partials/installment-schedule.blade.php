{{-- Jadwal pembayaran daftar ulang; hanya ada untuk camaba yang diberi jadwal oleh admin.
     Dikelompokkan per pembayaran agar camaba langsung tahu jumlah yang dibayar tiap jatuh tempo. --}}
@php
    $scheduleRupiah = fn ($amount) => 'Rp' . number_format((float) $amount, 0, ',', '.');
    $scheduleStatus = [
        'lunas' => ['Lunas', 'bg-green-100 text-green-700'],
        'sebagian' => ['Sebagian', 'bg-yellow-100 text-yellow-700'],
        'belum' => ['Belum Dibayar', 'bg-slate-100 text-slate-600'],
    ];
    $scheduleRows = $invoice->installmentSchedule();
    $installmentCounts = $scheduleRows->countBy(fn ($row) => $row['installment']->fee_component_id);
    $schedulePayments = $scheduleRows->groupBy(fn ($row) => $row['installment']->sequence)->sortKeys();
@endphp

<ol class="space-y-3">
    @foreach($schedulePayments as $sequence => $rows)
        @php
            $remaining = $rows->sum('remaining');
            $status = $remaining <= 0 ? 'lunas' : ($rows->sum('paid') > 0 ? 'sebagian' : 'belum');
            $dueDate = $rows->min(fn ($row) => $row['installment']->due_date);
            $overdue = $rows->contains('overdue', true);
        @endphp
        <li class="rounded-2xl border {{ $overdue ? 'border-red-200' : 'border-slate-200' }} bg-white p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-black text-slate-900">Pembayaran ke-{{ $sequence }}</p>
                    <p class="mt-0.5 text-xs {{ $overdue ? 'font-bold text-red-600' : 'text-slate-500' }}">
                        Jatuh tempo {{ $dueDate->translatedFormat('d/m/Y') }}{{ $overdue ? ' · lewat jatuh tempo' : '' }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-base font-black text-polmind-blue">{{ $scheduleRupiah($rows->sum(fn ($row) => (float) $row['installment']->amount)) }}</p>
                    <span class="mt-1 inline-block rounded-full px-2.5 py-1 text-xs font-black {{ $scheduleStatus[$status][1] }}">{{ $scheduleStatus[$status][0] }}</span>
                </div>
            </div>

            <ul class="mt-3 space-y-1 border-t border-slate-100 pt-3 text-sm">
                @foreach($rows as $row)
                    <li class="flex justify-between gap-3">
                        <span class="text-slate-600">
                            {{ $row['installment']->name }}
                            @if(($installmentCounts[$row['installment']->fee_component_id] ?? 1) > 1)
                                <span class="block text-xs text-slate-400">Angsuran ke-{{ $row['installment']->sequence }} dari {{ $installmentCounts[$row['installment']->fee_component_id] }}</span>
                            @endif
                        </span>
                        <span class="shrink-0 font-semibold text-slate-800">{{ $scheduleRupiah($row['installment']->amount) }}</span>
                    </li>
                @endforeach
            </ul>
        </li>
    @endforeach
</ol>
