{{-- Isian sumber informasi; $source null untuk form tambah --}}
@php
    $useOld = $source ? (int) old('_source_id') === $source->id : ! old('_source_id');
    $fieldValue = fn ($field, $default = null) => $useOld ? old($field, $default) : $default;
@endphp

<div class="md:col-span-2">
    <label class="{{ $label }}">Nama sumber informasi</label>
    <input type="text" name="name" value="{{ $fieldValue('name', $source?->name) }}" required maxlength="100"
           placeholder="Contoh: Facebook Ads, Pameran Pendidikan" class="{{ $input }}">
</div>

<div>
    <label class="{{ $label }}">Urutan tampil</label>
    <input type="number" name="sort_order" value="{{ $fieldValue('sort_order', $source?->sort_order ?? 0) }}" min="0" max="999" class="{{ $input }}">
</div>
