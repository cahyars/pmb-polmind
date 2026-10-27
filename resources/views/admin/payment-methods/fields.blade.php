{{-- Isian tujuan pembayaran. Variabel: $method (null saat tambah), $types, $input, $label, $optional --}}
@php
    $fieldId = fn (string $name) => $name . '_' . ($method?->id ?? 'new');
@endphp

<div>
    <label for="{{ $fieldId('type') }}" class="{{ $label }}">Jenis</label>
    <select id="{{ $fieldId('type') }}" name="type" required class="{{ $input }}">
        @foreach($types as $value => $typeLabel)
            <option value="{{ $value }}" @selected(old('type', $method?->type ?? 'bank_transfer') === $value)>{{ $typeLabel }}</option>
        @endforeach
    </select>
</div>

<div>
    <label for="{{ $fieldId('provider') }}" class="{{ $label }}">Nama Bank / Penyedia</label>
    <input id="{{ $fieldId('provider') }}" type="text" name="provider" required maxlength="100"
           value="{{ old('provider', $method?->provider) }}" placeholder="Contoh: Bank BCA"
           class="{{ $input }}">
</div>

<div>
    <label for="{{ $fieldId('account_number') }}" class="{{ $label }}">Nomor Rekening / Tujuan</label>
    <input id="{{ $fieldId('account_number') }}" type="text" name="account_number" maxlength="50"
           value="{{ old('account_number', $method?->account_number) }}" placeholder="Contoh: 1234567890"
           class="{{ $input }} font-mono">
    <p class="mt-1 text-xs text-slate-500">Wajib untuk transfer bank, virtual account, dan e-wallet.</p>
</div>

<div>
    <label for="{{ $fieldId('account_name') }}" class="{{ $label }}">Atas Nama {!! $optional !!}</label>
    <input id="{{ $fieldId('account_name') }}" type="text" name="account_name" maxlength="255"
           value="{{ old('account_name', $method?->account_name) }}" placeholder="Contoh: Politeknik Mitra Industri"
           class="{{ $input }}">
</div>

<div class="md:col-span-2">
    <label for="{{ $fieldId('instructions') }}" class="{{ $label }}">Petunjuk Pembayaran {!! $optional !!}</label>
    <textarea id="{{ $fieldId('instructions') }}" name="instructions" rows="3" maxlength="2000"
              placeholder="Contoh: Tambahkan nomor pendaftaran di berita transfer."
              class="{{ $input }}">{{ old('instructions', $method?->instructions) }}</textarea>
</div>

<div>
    <label for="{{ $fieldId('sort_order') }}" class="{{ $label }}">Urutan Tampil {!! $optional !!}</label>
    <input id="{{ $fieldId('sort_order') }}" type="number" name="sort_order" min="0" max="999"
           value="{{ old('sort_order', $method?->sort_order ?? 0) }}"
           class="{{ $input }}">
</div>

<label class="flex items-center gap-2 self-end rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $method?->is_active ?? true))>
    Tampilkan ke camaba
</label>
