{{-- Biaya --}}
<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Komponen Biaya</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Komponen biaya untuk invoice pendaftaran dan daftar ulang.
            </p>
        </div>

        <button type="button"
                onclick="document.getElementById('create-fee-component-form').classList.toggle('hidden')"
                class="rounded-lg bg-polmind-yellow px-5 py-3 text-sm font-bold text-polmind-blue-dark shadow-sm transition hover:brightness-95">
            Tambah Biaya
        </button>
    </div>

    <form id="create-fee-component-form"
        action="{{ route('admin.master-data.fee-components.store') }}"
        method="POST"
        class="hidden border-b border-slate-200 bg-slate-50 p-6">
        @csrf

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <input type="text" name="code" placeholder="Kode, contoh SPP1" required
                class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <input type="text" name="name" placeholder="Nama biaya" required
                class="xl:col-span-2 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <select name="type" required
                    class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                <option value="registration">Pendaftaran</option>
                <option value="re_registration">Daftar Ulang</option>
            </select>

            <input type="number" name="amount" value="0" min="0" required
                class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <input type="number" name="sort_order" value="1" min="0" required
                class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <label class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="is_active" value="1" checked>
                Aktif
            </label>

            <label class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 xl:col-span-2"
                   title="Khusus komponen daftar ulang, contoh UKT dan SPI">
                <input type="checkbox" name="is_installable" value="1">
                Bisa dicicil (daftar ulang)
            </label>
        </div>

        <div class="mt-4 flex justify-end gap-3">
            <button type="button"
                    onclick="document.getElementById('create-fee-component-form').classList.add('hidden')"
                    class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">
                Batal
            </button>

            <button type="submit"
                    class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">
                Simpan Biaya
            </button>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[1000px] text-left text-sm">
            <thead class="border-b border-slate-200 bg-white text-xs text-slate-400 [&_th]:font-medium">
                <tr>
                    <th class="px-5 py-3.5">No</th>
                    <th class="px-5 py-3.5">Urutan</th>
                    <th class="px-5 py-3.5">Kode</th>
                    <th class="px-5 py-3.5">Nama Biaya</th>
                    <th class="px-5 py-3.5">Tipe</th>
                    <th class="px-5 py-3.5">Nominal</th>
                    <th class="px-5 py-3.5">Aktif</th>
                    <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>

            <tbody class="table-zebra">
                @forelse($feeComponents as $fee)
                    <tr>
                        <td class="px-5 py-3.5 text-slate-500">{{ $loop->iteration }}</td>
                        <td class="px-5 py-3.5 font-medium text-slate-700">{{ $fee->sort_order }}</td>
                        <td class="px-5 py-3.5 font-bold text-polmind-blue">{{ $fee->code }}</td>
                        <td class="px-5 py-3.5 font-medium text-slate-800">
                            {{ $fee->name }}
                            @if($fee->is_installable)
                                <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-700">Bisa dicicil</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-polmind-blue">
                                {{ $typeLabels[$fee->type] ?? $fee->type }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 font-bold text-green-700">
                            Rp{{ number_format($fee->amount, 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-3.5">
                            @if($fee->is_active)
                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-700">Aktif</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex justify-end gap-2">
                                <button type="button"
                                        onclick="document.getElementById('edit-fee-component-{{ $fee->id }}').classList.toggle('hidden')"
                                        class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-polmind-blue">
                                    Edit
                                </button>

                                <form action="{{ route('admin.master-data.fee-components.toggle', $fee) }}"
                                    method="POST"
                                    onsubmit="return confirm('Ubah status aktif komponen biaya ini?')">
                                    @csrf
                                    @method('PATCH')

                                    <button type="submit"
                                            class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">
                                        {{ $fee->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <tr id="edit-fee-component-{{ $fee->id }}" class="hidden bg-slate-50">
                        <td colspan="8" class="px-6 py-5">
                            <form action="{{ route('admin.master-data.fee-components.update', $fee) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                                    <input type="text" name="code" value="{{ $fee->code }}" required
                                        class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <input type="text" name="name" value="{{ $fee->name }}" required
                                        class="xl:col-span-2 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <select name="type" required
                                            class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                        <option value="registration" @selected($fee->type === 'registration')>Pendaftaran</option>
                                        <option value="re_registration" @selected($fee->type === 're_registration')>Daftar Ulang</option>
                                    </select>

                                    <input type="number" name="amount" value="{{ $fee->amount }}" min="0" required
                                        class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <input type="number" name="sort_order" value="{{ $fee->sort_order }}" min="0" required
                                        class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <label class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">
                                        <input type="checkbox" name="is_active" value="1" @checked($fee->is_active)>
                                        Aktif
                                    </label>

                                    <label class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 xl:col-span-2"
                                           title="Khusus komponen daftar ulang, contoh UKT dan SPI">
                                        <input type="checkbox" name="is_installable" value="1" @checked($fee->is_installable)>
                                        Bisa dicicil (daftar ulang)
                                    </label>
                                </div>

                                <div class="mt-4 flex justify-end gap-3">
                                    <button type="button"
                                            onclick="document.getElementById('edit-fee-component-{{ $fee->id }}').classList.add('hidden')"
                                            class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">
                                        Batal
                                    </button>

                                    <button type="submit"
                                            class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">
                                        Update Biaya
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-sm font-medium text-slate-500">
                            Belum ada data komponen biaya.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Nominal daftar ulang dan template cicilan per jalur --}}
@if($reRegistrationFees->isNotEmpty() && $registrationPaths->isNotEmpty())
    @php
        $oldPathId = (int) old('_fee_path_id');
        $input = 'w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100';
    @endphp

    <div x-data="{ tab: @js((int) session('fee_path_tab', $oldPathId ?: $registrationPaths->first()->id)) }"
         class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-6">
            <h2 class="text-base font-semibold text-slate-900">Biaya Daftar Ulang per Jalur</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Kosongkan nominal untuk memakai nominal default. Nominal berlaku untuk invoice daftar ulang yang dibuat setelah disimpan.
            </p>
            <p class="mt-2 text-xs leading-5 text-slate-500">
                Template cicilan hanya dipakai admin saat mengaktifkan cicilan dari halaman detail camaba, dan tidak ditampilkan ke camaba maupun di website.
            </p>

            <div class="mt-5 flex flex-wrap gap-2">
                @foreach($registrationPaths as $path)
                    <button type="button"
                            @click="tab = {{ $path->id }}"
                            :class="tab === {{ $path->id }} ? 'bg-polmind-blue text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50'"
                            class="rounded-lg px-4 py-2 text-sm font-semibold transition">
                        {{ $path->name }}
                        @unless($path->is_active)
                            <span class="text-xs font-normal opacity-70">(nonaktif)</span>
                        @endunless
                    </button>
                @endforeach
            </div>
        </div>

        @foreach($registrationPaths as $path)
            @php $useOld = $oldPathId === $path->id; @endphp

            <form x-show="tab === {{ $path->id }}" x-cloak
                  action="{{ route('admin.master-data.registration-paths.fees', $path) }}"
                  method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="_fee_path_id" value="{{ $path->id }}">

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="border-b border-slate-200 bg-white text-xs text-slate-400 [&_th]:font-medium">
                            <tr>
                                <th class="px-5 py-3.5">No</th>
                                <th class="px-5 py-3.5">Komponen</th>
                                <th class="px-5 py-3.5">Nominal Default</th>
                                <th class="w-64 px-5 py-3.5">Nominal Jalur {{ $path->name }}</th>
                            </tr>
                        </thead>

                        @foreach($reRegistrationFees as $fee)
                            @php
                                $override = $fee->pathAmounts->firstWhere('registration_path_id', $path->id)?->amount;
                                $templateRows = $fee->installmentTemplates
                                    ->where('registration_path_id', $path->id)
                                    ->map(fn ($row) => ['amount' => (float) $row->amount, 'due_date' => $row->due_date->toDateString()])
                                    ->values();
                                $rows = $useOld ? collect(old('installments.' . $fee->id, []))->values() : $templateRows;
                                $custom = $useOld ? old('amounts.' . $fee->id) : ($override !== null ? (float) $override : '');
                            @endphp

                            <tbody x-data="{
                                       base: {{ (float) $fee->amount }},
                                       custom: @js($custom),
                                       rows: @js($rows),
                                       get effective() { return Number(this.custom === '' || this.custom === null ? this.base : this.custom) },
                                       get total() { return this.rows.reduce((sum, row) => sum + Number(row.amount || 0), 0) },
                                       rupiah(value) { return 'Rp' + new Intl.NumberFormat('id-ID').format(value) },
                                   }"
                                   class="border-b border-slate-100">
                                <tr>
                                    <td class="px-5 py-3.5 text-slate-500">{{ $loop->iteration }}</td>
                                    <td class="px-5 py-3.5 font-medium text-slate-800">
                                        {{ $fee->name }}
                                        @if($fee->is_installable)
                                            <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-700">Bisa dicicil</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-600">Rp{{ number_format($fee->amount, 0, ',', '.') }}</td>
                                    <td class="px-5 py-3.5">
                                        <input type="number" name="amounts[{{ $fee->id }}]" x-model="custom" min="0"
                                               placeholder="{{ (float) $fee->amount }}"
                                               title="Kosongkan untuk memakai nominal default"
                                               class="{{ $input }}">
                                    </td>
                                </tr>

                                @if($fee->is_installable)
                                    <tr class="bg-slate-50/60">
                                        <td></td>
                                        <td colspan="3" class="px-5 pb-4 pt-1">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <p class="text-xs font-semibold text-slate-600">Template cicilan {{ $fee->name }}</p>
                                                <p class="text-xs" x-show="rows.length"
                                                   :class="Math.round(total) === Math.round(effective) ? 'text-green-700' : 'font-semibold text-red-600'">
                                                    Total angsuran <span x-text="rupiah(total)"></span> dari <span x-text="rupiah(effective)"></span>
                                                </p>
                                            </div>

                                            <template x-for="(row, index) in rows" :key="index">
                                                <div class="mt-2 grid grid-cols-[auto_1fr_1fr_auto] items-center gap-2">
                                                    <span class="w-24 text-xs font-semibold text-slate-500" x-text="'Angsuran ' + (index + 1)"></span>
                                                    <input type="number" min="1" required x-model="row.amount"
                                                           :name="`installments[{{ $fee->id }}][${index}][amount]`"
                                                           placeholder="Nominal"
                                                           class="{{ $input }}">
                                                    <input type="date" required x-model="row.due_date"
                                                           :name="`installments[{{ $fee->id }}][${index}][due_date]`"
                                                           title="Tanggal jatuh tempo"
                                                           class="{{ $input }}">
                                                    <button type="button" @click="rows.splice(index, 1)"
                                                            class="rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">
                                                        Hapus
                                                    </button>
                                                </div>
                                            </template>

                                            <button type="button"
                                                    @click="rows.push({ amount: rows.length ? '' : effective, due_date: '' })"
                                                    class="mt-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-polmind-blue">
                                                + Tambah angsuran
                                            </button>
                                            <p x-show="! rows.length" class="mt-2 text-xs text-slate-500">Belum ada template. Saat cicilan diaktifkan, jadwal awal diisi satu kali bayar penuh.</p>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        @endforeach
                    </table>
                </div>

                <div class="flex justify-end border-t border-slate-200 p-6">
                    <button type="submit" class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">
                        Simpan Biaya Jalur {{ $path->name }}
                    </button>
                </div>
            </form>
        @endforeach
    </div>
@endif
