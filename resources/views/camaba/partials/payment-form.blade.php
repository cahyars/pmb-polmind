{{-- Form unggah bukti transfer. Formulir: nominal tetap ($amount). Daftar ulang: camaba memilih
     komponen ($components, saldo dari Invoice::componentBalances) dan nominal dijumlahkan otomatis. --}}
@php
    $rupiah = fn ($value) => 'Rp' . number_format((float) $value, 0, ',', '.');
    $isComponentForm = isset($components);
    $hasFailedUpload = old('_card') === $cardKey;
    $checkedComponents = $isComponentForm
        ? collect($hasFailedUpload ? old('components', []) : [$primaryComponentId])->map(fn ($id) => (int) $id)->all()
        : [];
    $choices = $isComponentForm
        ? $components->map(fn ($balance) => [
            'id' => (int) $balance['fee_component_id'],
            'name' => $balance['item']->feeComponent?->name ?? $balance['item']->name,
            'amount' => $balance['due'],
            'installment' => $balance['schedule']->count() > 1 ? $balance['schedule']->first(fn ($row) => $row['remaining'] > 0)['installment']->sequence : null,
        ])->values()
        : collect();
@endphp

<form action="{{ route('camaba.payments.upload-proof', $invoice->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6"
      x-data="{
          choices: @js($choices),
          selected: @js($checkedComponents),
          get total() { return this.choices.filter(choice => this.selected.includes(choice.id)).reduce((sum, choice) => sum + choice.amount, 0) },
          rupiah(value) { return 'Rp' + new Intl.NumberFormat('id-ID').format(value) },
      }">
    @csrf
    <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
    <input type="hidden" name="_card" value="{{ $cardKey }}">

    @if($isComponentForm)
        {{-- Langkah 0: komponen yang dibayar --}}
        <fieldset>
            <legend class="text-base font-black text-slate-900">Komponen yang dibayar</legend>
            <p class="mt-1 text-xs text-slate-500">Boleh dibayar satu per satu atau sekaligus dalam satu transfer.</p>

            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                @foreach($choices as $choice)
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-2xl border border-slate-300 bg-white px-4 py-3 transition has-checked:border-polmind-blue has-checked:bg-blue-50">
                        <span class="flex items-center gap-3">
                            <input type="checkbox" name="components[]" value="{{ $choice['id'] }}" x-model.number="selected" class="h-4 w-4 accent-polmind-blue">
                            <span class="text-sm font-bold text-slate-800">
                                {{ $choice['name'] }}
                                @if($choice['installment'])
                                    <span class="block text-xs font-normal text-slate-500">Angsuran ke-{{ $choice['installment'] }}</span>
                                @endif
                            </span>
                        </span>
                        <span class="shrink-0 text-sm font-black text-polmind-blue">{{ $rupiah($choice['amount']) }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endif

    {{-- Langkah 1 --}}
    <fieldset>
        <legend class="flex items-center gap-2 text-base font-black text-slate-900">
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-polmind-blue text-xs font-black text-white">1</span>
            @if($isComponentForm)
                <span>Transfer <span x-text="rupiah(total)">{{ $rupiah($choices->whereIn('id', $checkedComponents)->sum('amount')) }}</span> ke salah satu rekening</span>
            @else
                Transfer {{ $rupiah($amount) }} ke salah satu rekening
            @endif
        </legend>

        @if($paymentMethods->isEmpty())
            <p class="mt-3 rounded-2xl bg-slate-50 p-4 text-sm font-semibold text-slate-600">
                Informasi rekening pembayaran belum tersedia. Silakan hubungi admin PMB melalui WhatsApp {{ \App\Services\AdminWhatsApp::display() }}.
            </p>
        @else
            <p class="mt-1 pl-9 text-xs text-slate-500">Pilih rekening yang Anda gunakan untuk transfer.</p>

            <div class="mt-3 grid gap-3 md:grid-cols-2">
                @foreach($paymentMethods as $method)
                    <label x-data="{ copied: false }"
                           class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-300 bg-white p-4 transition has-checked:border-polmind-blue has-checked:bg-blue-50 has-checked:ring-2 has-checked:ring-blue-100">
                        <input type="radio" name="payment_method_id" value="{{ $method->id }}" required class="mt-1 accent-polmind-blue"
                               @checked((string) ($hasFailedUpload ? old('payment_method_id') : ($paymentMethods->count() === 1 ? $method->id : '')) === (string) $method->id)>
                        <span class="min-w-0 flex-1">
                            <span class="block text-[11px] font-black uppercase tracking-wide text-slate-500">{{ $method->type_label }}</span>
                            <span class="block font-black text-slate-900">{{ $method->provider }}</span>
                            @if($method->account_number)
                                <span class="mt-1 flex items-center justify-between gap-2">
                                    <span class="font-mono text-base font-black tracking-wider text-polmind-blue">{{ $method->account_number }}</span>
                                    <button type="button"
                                            @click.prevent="navigator.clipboard.writeText(@js($method->account_number)); copied = true; setTimeout(() => copied = false, 2000)"
                                            class="shrink-0 rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs font-bold text-slate-700 hover:bg-slate-100"
                                            x-text="copied ? '✓ Tersalin' : 'Salin'">Salin</button>
                                </span>
                            @endif
                            @if($method->account_name)
                                <span class="mt-1 block text-xs text-slate-600">a.n. <b>{{ $method->account_name }}</b></span>
                            @endif
                            @if($method->instructions)
                                <span class="mt-2 block whitespace-pre-line text-xs leading-5 text-slate-500">{{ $method->instructions }}</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>
        @endif
    </fieldset>

    {{-- Langkah 2 --}}
    <fieldset>
        <legend class="flex items-center gap-2 text-base font-black text-slate-900">
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-polmind-blue text-xs font-black text-white">2</span>
            Isi data transfer & unggah bukti
        </legend>

        <div class="mt-3 grid gap-4 md:grid-cols-2">
            <div>
                <label for="sender_name_{{ $cardKey }}" class="text-sm font-bold text-slate-700">Nama Pengirim</label>
                <input id="sender_name_{{ $cardKey }}" type="text" name="sender_name" required
                       value="{{ $hasFailedUpload ? old('sender_name') : '' }}"
                       placeholder="Sesuai nama di rekening/e-wallet"
                       class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div>
                <label for="sender_bank_{{ $cardKey }}" class="text-sm font-bold text-slate-700">Bank / E-Wallet Pengirim</label>
                <input id="sender_bank_{{ $cardKey }}" type="text" name="sender_bank" required
                       value="{{ $hasFailedUpload ? old('sender_bank') : '' }}"
                       placeholder="Contoh: BRI, DANA"
                       class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div>
                <label for="transfer_date_{{ $cardKey }}" class="text-sm font-bold text-slate-700">Tanggal Transfer</label>
                <input id="transfer_date_{{ $cardKey }}" type="date" name="transfer_date" required
                       max="{{ now()->format('Y-m-d') }}"
                       value="{{ $hasFailedUpload ? old('transfer_date', now()->format('Y-m-d')) : now()->format('Y-m-d') }}"
                       class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div>
                <label for="amount_{{ $cardKey }}" class="text-sm font-bold text-slate-700">Nominal Bayar</label>
                @if($isComponentForm)
                    <input id="amount_{{ $cardKey }}" type="text" readonly :value="rupiah(total)"
                           class="mt-2 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-bold outline-none">
                    <p class="mt-1.5 text-xs text-slate-500">Jumlah komponen yang dipilih.</p>
                @else
                    <input id="amount_{{ $cardKey }}" type="number" name="amount" required readonly value="{{ $amount }}"
                           class="mt-2 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none">
                    <p class="mt-1.5 text-xs text-slate-500">Sesuai biaya formulir.</p>
                @endif
            </div>

            <div class="md:col-span-2" x-data="{ fileName: '' }">
                <span class="text-sm font-bold text-slate-700">Bukti Pembayaran</span>
                <label class="mt-2 flex cursor-pointer items-center gap-3 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-4 transition hover:border-polmind-blue hover:bg-blue-50 has-focus-visible:border-polmind-blue">
                    <x-icon name="upload" class="h-6 w-6 shrink-0 text-polmind-blue" />
                    <span class="min-w-0 text-sm">
                        <span class="block truncate font-bold text-slate-800" x-text="fileName || 'Pilih foto atau PDF bukti transfer'">Pilih foto atau PDF bukti transfer</span>
                        <span class="block text-xs text-slate-500">JPG, PNG, atau PDF · maks. 4 MB</span>
                    </span>
                    <input type="file" name="proof_file" accept=".jpg,.jpeg,.png,.pdf" required class="sr-only"
                           @change="fileName = $event.target.files[0]?.name ?? ''">
                </label>
            </div>
        </div>
    </fieldset>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs leading-5 text-slate-500">Setelah dikirim, admin PMB memverifikasi maksimal 1x24 jam kerja.</p>
        <button type="submit" @if($isComponentForm) :disabled="! selected.length" @endif
                class="inline-flex justify-center rounded-xl bg-polmind-blue px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark disabled:cursor-not-allowed disabled:opacity-50">
            Kirim Bukti Pembayaran
        </button>
    </div>
</form>
