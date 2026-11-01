{{-- Isian kategori prestasi; $level null untuk form tambah. --}}
@php
    $field = 'rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100';
    $useOld = old('_achievement_form') === ($level ? (string) $level->id : 'create');
    $value = fn ($key, $default) => $useOld ? old($key, $default) : $default;
@endphp

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
    <input type="text" name="name" required maxlength="255" placeholder="Nama kategori, contoh Tingkat 1 · Nasional"
           value="{{ $value('name', $level?->name) }}" aria-label="Nama kategori"
           class="xl:col-span-2 {{ $field }}">

    <input type="number" name="spi_amount" required min="0" step="1000" placeholder="Nominal SPI"
           value="{{ $value('spi_amount', $level ? (float) $level->spi_amount : '') }}" aria-label="Nominal SPI"
           class="{{ $field }}">

    <input type="number" name="sort_order" required min="0" placeholder="Urutan"
           value="{{ $value('sort_order', $level?->sort_order ?? 1) }}" aria-label="Urutan"
           class="{{ $field }}">

    <label class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 xl:col-span-2">
        <input type="checkbox" name="is_active" value="1" @checked($value('is_active', $level?->is_active ?? true))>
        Aktif
    </label>

    <textarea name="description" rows="2" maxlength="1000" placeholder="Kriteria, contoh juara 1-3 lomba tingkat nasional"
              aria-label="Kriteria" class="md:col-span-2 xl:col-span-6 {{ $field }}">{{ $value('description', $level?->description) }}</textarea>
</div>
