{{-- Dropdown Provinsi > Kab/Kota > Kecamatan. Dipakai di dalam x-data regionPicker/schoolPicker.
     $regionRequired = false untuk alamat yang boleh dikosongkan (form pendaftar di kampus). --}}
@php
    $regionRequired = $regionRequired ?? true;
    $regionField = 'mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100 disabled:bg-slate-100 disabled:text-slate-400';
@endphp

<div>
    <label for="{{ $fieldName }}_province" class="text-sm font-bold text-slate-700">Provinsi @if($regionRequired)<span class="text-red-500">*</span>@endif</label>
    <select id="{{ $fieldName }}_province" x-model="province" @change="changeProvince()" @required($regionRequired) class="{{ $regionField }}">
        <option value="">Pilih provinsi</option>
        @foreach($provinces as $province)
            <option value="{{ $province->id }}">{{ $province->name }}</option>
        @endforeach
    </select>
</div>

<div>
    <label for="{{ $fieldName }}_regency" class="text-sm font-bold text-slate-700">Kabupaten / Kota @if($regionRequired)<span class="text-red-500">*</span>@endif</label>
    <select id="{{ $fieldName }}_regency" x-model="regency" @change="changeRegency()" @required($regionRequired) :disabled="! regencies.length" class="{{ $regionField }}">
        <option value="">Pilih kabupaten/kota</option>
        <template x-for="item in regencies" :key="item.id">
            <option :value="item.id" x-text="item.name"></option>
        </template>
    </select>
</div>

<div>
    <label for="{{ $fieldName }}" class="text-sm font-bold text-slate-700">Kecamatan @if($regionRequired)<span class="text-red-500">*</span>@endif</label>
    <select id="{{ $fieldName }}" name="{{ $fieldName }}" x-model="district" @required($regionRequired) :disabled="! districts.length" class="{{ $regionField }}">
        <option value="">Pilih kecamatan</option>
        <template x-for="item in districts" :key="item.id">
            <option :value="item.id" x-text="item.name"></option>
        </template>
    </select>
    <p x-show="loading" x-cloak class="mt-1 text-xs text-slate-500">Memuat data wilayah...</p>
</div>
