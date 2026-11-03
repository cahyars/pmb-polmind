{{-- Isian kelompok tes; $group null untuk form tambah --}}
@php
    $checked = $group ? $group->components->pluck('id')->all() : $selectionComponents->pluck('id')->all();
@endphp
<div>
    <label class="{{ $label }}">Nama kelompok</label>
    <input type="text" name="name" value="{{ $group?->name }}" required maxlength="100" placeholder="Kelompok 4" class="{{ $input }}">
</div>

<div>
    <label class="{{ $label }}">Urutan</label>
    <input type="number" name="sort_order" value="{{ $group?->sort_order ?? $groups->count() + 1 }}" min="0" max="999" class="{{ $input }}">
</div>

<div class="md:col-span-2">
    <label class="{{ $label }}">Keterangan <span class="font-semibold text-slate-400">(opsional)</span></label>
    <input type="text" name="notes" value="{{ $group?->notes }}" maxlength="255" placeholder="Contoh: Ruang Lab 2" class="{{ $input }}">
</div>

<fieldset class="md:col-span-2">
    <legend class="{{ $label }}">Tes yang diikuti</legend>
    <div class="mt-2 flex flex-wrap gap-2">
        @foreach($selectionComponents as $component)
            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm has-[:checked]:border-polmind-blue has-[:checked]:bg-blue-50">
                <input type="checkbox" name="components[]" value="{{ $component->id }}" @checked(in_array($component->id, $checked)) class="rounded border-slate-300 text-polmind-blue">
                {{ $component->name }}
            </label>
        @endforeach
    </div>
</fieldset>

<label class="flex items-start gap-3 md:col-span-2">
    <input type="checkbox" name="is_partner" value="1" @checked($group?->is_partner) class="mt-1 rounded border-slate-300 text-polmind-blue">
    <span class="text-sm text-slate-700">
        <span class="font-semibold">Kelompok mitra</span>
        <span class="block text-xs text-slate-500">Bagi otomatis memasukkan pendaftar SMK Mitra Industri dan SMK Ananda Mitra Industri ke kelompok ini, dan daftar hadirnya dicetak terpisah.</span>
    </span>
</label>
