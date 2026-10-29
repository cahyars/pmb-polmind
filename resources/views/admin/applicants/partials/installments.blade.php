{{-- Skema cicilan daftar ulang (khusus Admin PMB). Pengajuan dilakukan di luar sistem. --}}
@php
    $rupiah = fn ($amount) => 'Rp' . number_format((float) $amount, 0, ',', '.');
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100';
    $enabled = $invoice->hasInstallments();
    $schedule = $enabled ? $invoice->installmentSchedule() : collect();
    $statusBadges = [
        'lunas' => ['Lunas', 'bg-green-100 text-green-700'],
        'sebagian' => ['Sebagian', 'bg-yellow-100 text-yellow-700'],
        'belum' => ['Belum Bayar', 'bg-slate-100 text-slate-600'],
    ];
    $canEdit = $invoice->status !== 'paid';
    $hasInstallable = $installmentRows->contains('installable', true);
@endphp

<div x-data="{ editing: {{ old('_installment_form') ? 'true' : 'false' }} }"
     class="rounded-xl border border-amber-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col justify-between gap-3 md:flex-row md:items-start">
        <div>
            <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900">
                <x-icon name="lock" class="h-4 w-4 text-amber-600" stroke-width="2" />
                Skema Cicilan Daftar Ulang
            </h2>
            <p class="mt-1 text-xs leading-5 text-slate-500">
                Khusus Admin PMB, untuk camaba yang mengajukan di luar sistem. Jadwal hanya terlihat oleh camaba ini setelah diaktifkan.
                Daftar ulang valid setelah angsuran ke-1 terbayar.
            </p>
        </div>

        @if($enabled)
            <span class="shrink-0 rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">Cicilan aktif</span>
        @else
            <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">Bayar sekaligus</span>
        @endif
    </div>

    @if($enabled)
        <div class="mt-5 overflow-x-auto">
            <table class="w-full min-w-[620px] text-left text-sm">
                <thead class="border-b border-slate-200 text-xs text-slate-400 [&_th]:font-medium">
                    <tr>
                        <th class="px-3 py-2.5">No</th>
                        <th class="px-3 py-2.5">Komponen</th>
                        <th class="px-3 py-2.5">Angsuran</th>
                        <th class="px-3 py-2.5">Jatuh Tempo</th>
                        <th class="px-3 py-2.5 text-right">Nominal</th>
                        <th class="px-3 py-2.5">Status</th>
                    </tr>
                </thead>
                <tbody class="table-zebra">
                    @foreach($schedule as $row)
                        <tr>
                            <td class="px-3 py-2.5 text-slate-500">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2.5 font-medium text-slate-800">{{ $row['installment']->name }}</td>
                            <td class="px-3 py-2.5 text-slate-600">Ke-{{ $row['installment']->sequence }}</td>
                            <td class="px-3 py-2.5 {{ $row['overdue'] ? 'font-semibold text-red-600' : 'text-slate-600' }}">
                                {{ $row['installment']->due_date->translatedFormat('d M Y') }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-semibold text-slate-900">{{ $rupiah($row['installment']->amount) }}</td>
                            <td class="px-3 py-2.5">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusBadges[$row['status']][1] }}">{{ $statusBadges[$row['status']][0] }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="mt-3 text-xs leading-5 text-slate-500">
            Diaktifkan {{ $invoice->installment_enabled_at->translatedFormat('d M Y H:i') }}, terakhir diubah oleh {{ $invoice->installment_enabled_by_name ?? '-' }}.
            Angsuran ke-1: <span class="font-semibold text-slate-700">{{ $rupiah($invoice->firstInstallmentAmount()) }}</span>.
            @if($invoice->installment_note)
                <br>Catatan internal: {{ $invoice->installment_note }}
            @endif
        </p>
    @endif

    @if(! $canEdit)
        <p class="mt-4 rounded-lg bg-green-50 p-4 text-sm text-green-800">Tagihan daftar ulang sudah lunas.</p>
    @elseif(! $hasInstallable)
        <p class="mt-4 rounded-lg bg-slate-50 p-4 text-sm leading-6 text-slate-600">
            Belum ada komponen di invoice ini yang ditandai <span class="font-semibold">Bisa dicicil</span>.
            Tandai komponen UKT/SPI di Master Data Komponen Biaya, lalu terbitkan ulang invoice daftar ulang bila perlu.
        </p>
    @else
        <div class="mt-5 flex flex-wrap gap-2">
            <button type="button" @click="editing = ! editing"
                    class="rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-bold text-white transition hover:brightness-95">
                {{ $enabled ? 'Ubah Jadwal Cicilan' : 'Aktifkan Cicilan' }}
            </button>

            @if($enabled)
                <form action="{{ route('admin.applicants.installments.destroy', $applicant->registration_number) }}"
                      method="POST"
                      onsubmit="return confirm('Nonaktifkan cicilan? Tagihan daftar ulang kembali dibayar sekaligus.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Nonaktifkan Cicilan
                    </button>
                </form>
            @endif
        </div>

        <form x-show="editing" x-cloak
              action="{{ route('admin.applicants.installments.update', $applicant->registration_number) }}"
              method="POST"
              class="mt-5 space-y-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="_installment_form" value="1">

            @unless($enabled)
                <p class="text-xs leading-5 text-slate-600">Jadwal awal diisi dari template cicilan jalur {{ $applicant->registration_path_label }} di Master Data. Sesuaikan bila perlu.</p>
            @endunless

            @foreach($installmentRows as $entry)
                @php
                    $item = $entry['item'];
                    $rows = old('installments.' . $item->id, $entry['rows']);
                @endphp

                @if($entry['installable'])
                    <div x-data="{
                             target: {{ (float) $item->subtotal }},
                             rows: @js(array_values($rows)),
                             get total() { return this.rows.reduce((sum, row) => sum + Number(row.amount || 0), 0) },
                             rupiah(value) { return 'Rp' + new Intl.NumberFormat('id-ID').format(value) },
                         }"
                         class="rounded-lg border border-slate-200 bg-white p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-800">{{ $item->name }} · {{ $rupiah($item->subtotal) }}</p>
                            <p class="text-xs" :class="Math.round(total) === Math.round(target) ? 'text-green-700' : 'font-semibold text-red-600'">
                                Total angsuran <span x-text="rupiah(total)"></span>
                            </p>
                        </div>

                        <template x-for="(row, index) in rows" :key="index">
                            <div class="mt-2 grid grid-cols-[auto_1fr_1fr_auto] items-center gap-2">
                                <span class="w-20 text-xs font-semibold text-slate-500" x-text="'Ke-' + (index + 1)"></span>
                                <input type="number" min="1" required x-model="row.amount"
                                       :name="`installments[{{ $item->id }}][${index}][amount]`"
                                       placeholder="Nominal" class="{{ $input }}">
                                <input type="date" required x-model="row.due_date"
                                       :name="`installments[{{ $item->id }}][${index}][due_date]`"
                                       title="Tanggal jatuh tempo" class="{{ $input }}">
                                <button type="button" @click="rows.splice(index, 1)" x-show="rows.length > 1"
                                        class="rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">
                                    Hapus
                                </button>
                            </div>
                        </template>

                        <button type="button" @click="rows.push({ amount: '', due_date: '' })"
                                class="mt-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-polmind-blue">
                            + Tambah angsuran
                        </button>
                    </div>
                @else
                    <div class="flex justify-between rounded-lg border border-dashed border-slate-300 bg-white px-4 py-3 text-sm">
                        <span class="text-slate-700">{{ $item->name }} <span class="text-xs text-slate-500">· dibayar penuh bersama angsuran ke-1</span></span>
                        <span class="font-semibold text-slate-900">{{ $rupiah($item->subtotal) }}</span>
                    </div>
                @endif
            @endforeach

            <div>
                <label for="installment_note" class="text-sm font-semibold text-slate-700">Catatan internal <span class="font-normal text-slate-500">(tidak tampil ke camaba)</span></label>
                <textarea id="installment_note" name="installment_note" rows="2" maxlength="1000"
                          placeholder="Contoh: Disetujui Wadir II, kondisi darurat keluarga"
                          class="mt-2 {{ $input }}">{{ old('installment_note', $invoice->installment_note) }}</textarea>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" @click="editing = false" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</button>
                <button type="submit" class="rounded-lg bg-polmind-blue px-4 py-2.5 text-sm font-semibold text-white">Simpan Skema Cicilan</button>
            </div>
        </form>
    @endif
</div>
