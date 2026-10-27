{{-- Lokasi + pencarian sekolah asal (atau isian manual). Dipakai di dalam x-data schoolPicker. --}}
@php
    $field = 'mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100 disabled:bg-slate-100 disabled:text-slate-500';
    $label = 'text-sm font-bold text-slate-700';
    $hint = 'mt-1 text-xs leading-5 text-slate-500';
    $optional = '<span class="font-semibold text-slate-400">(opsional)</span>';
    $required = '<span class="text-red-500">*</span>';
@endphp

<div>
    <p class="text-sm font-black text-slate-900">1. Lokasi sekolah</p>
    <p class="{{ $hint }}">Pilih wilayah sekolah asal Anda, lalu cari nama sekolahnya.</p>
    <div class="mt-2 grid gap-5 md:grid-cols-3">
        @include('camaba.partials.region-select', ['fieldName' => 'school_region_id'])
    </div>
</div>

<div>
    <p class="text-sm font-black text-slate-900">2. Nama sekolah {!! $required !!}</p>

    <input type="hidden" name="is_manual_school" :value="manual ? 1 : 0">
    <input type="hidden" name="school_npsn" :value="npsn">
    <input type="hidden" name="school_name" :value="name">
    <input type="hidden" name="school_type" :value="type">
    <input type="hidden" name="school_status" :value="status">

    {{-- Sekolah terpilih --}}
    <div x-show="name && ! manual" x-cloak class="mt-3 flex items-start justify-between gap-4 rounded-2xl border border-green-300 bg-green-50 p-4">
        <div>
            <p class="font-black text-green-900" x-text="name"></p>
            <p class="mt-1 text-xs text-green-800/80">
                NPSN <span x-text="npsn"></span> · <span x-text="type"></span> <span x-text="status"></span>
            </p>
        </div>
        <button type="button" @click="clearSchool()" class="shrink-0 text-xs font-bold text-green-800 underline">Ganti</button>
    </div>

    {{-- Pencarian --}}
    <div x-show="! manual && ! name" class="mt-3">
        <div x-show="! regency" class="rounded-2xl border border-dashed border-slate-300 p-5 text-center text-sm text-slate-500">
            Pilih provinsi dan kabupaten/kota terlebih dahulu.
        </div>

        <div x-show="regency" x-cloak>
            <input type="search" x-model.debounce.200ms="query" placeholder="Ketik nama sekolah, contoh: SMKN 1"
                   class="{{ $field }}" aria-label="Cari nama sekolah">

            <p x-show="loadingSchools" class="{{ $hint }}">Memuat daftar sekolah...</p>

            <p x-show="! loadingSchools && ! available" class="mt-3 rounded-xl bg-yellow-50 px-4 py-3 text-xs font-semibold text-yellow-800">
                Daftar sekolah sedang tidak dapat dimuat. Silakan isi data sekolah secara manual di bawah.
            </p>

            <ul x-show="! loadingSchools && available" class="mt-3 max-h-72 divide-y divide-slate-100 overflow-y-auto rounded-2xl border border-slate-200">
                <template x-for="school in filteredSchools" :key="school.npsn">
                    <li>
                        <button type="button" @click="pickSchool(school)"
                                class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left transition hover:bg-blue-50">
                            <span>
                                <span class="block text-sm font-bold text-slate-800" x-text="school.name"></span>
                                <span class="block text-xs text-slate-500" x-text="'NPSN ' + school.npsn + (school.address ? ' · ' + school.address : '')"></span>
                            </span>
                            <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-black text-slate-600" x-text="school.type + ' ' + school.status"></span>
                        </button>
                    </li>
                </template>
                <li x-show="filteredSchools.length === 0" class="px-4 py-5 text-center text-sm text-slate-500">
                    Sekolah tidak ditemukan. Coba kata kunci lain atau isi manual.
                </li>
            </ul>
            <p x-show="district && available && ! loadingSchools" class="{{ $hint }}">
                <span x-show="! showAllDistricts">Menampilkan sekolah di kecamatan terpilih.</span>
                <button type="button" class="font-bold text-polmind-blue underline" @click="showAllDistricts = ! showAllDistricts"
                        x-text="showAllDistricts ? 'Tampilkan kecamatan terpilih saja' : 'Tampilkan semua sekolah di kab/kota'"></button>
            </p>
        </div>
    </div>

    <label class="mt-4 flex cursor-pointer items-start gap-3 text-sm text-slate-700">
        <input type="checkbox" class="mt-0.5" :checked="manual" @change="setManual($event.target.checked)">
        <span>
            <span class="font-bold">Sekolah saya tidak ada di daftar</span>
            <span class="block text-xs text-slate-500">Misalnya MA/madrasah, Paket C, atau sekolah baru.</span>
        </span>
    </label>

    {{-- Isian manual --}}
    <div x-show="manual" x-cloak class="mt-4 grid gap-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-2">
        <div class="md:col-span-2">
            <label for="manual_school_name" class="{{ $label }}">Nama Sekolah {!! $required !!}</label>
            <input id="manual_school_name" type="text" x-model="name" :required="manual" maxlength="255"
                   placeholder="Contoh: MAN 1 KOTA BEKASI" class="{{ $field }} uppercase">
        </div>
        <div>
            <label for="manual_school_type" class="{{ $label }}">Jenis Sekolah {!! $required !!}</label>
            <select id="manual_school_type" x-model="type" :required="manual" class="{{ $field }}">
                <option value="">Pilih</option>
                @foreach($schoolTypes as $schoolType)
                    <option value="{{ $schoolType }}">{{ $schoolType }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="manual_school_status" class="{{ $label }}">Status Sekolah {!! $optional !!}</label>
            <select id="manual_school_status" x-model="status" class="{{ $field }}">
                <option value="">Pilih</option>
                <option value="Negeri">Negeri</option>
                <option value="Swasta">Swasta</option>
            </select>
        </div>
        <div>
            <label for="manual_school_npsn" class="{{ $label }}">NPSN {!! $optional !!}</label>
            <input id="manual_school_npsn" type="text" x-model="npsn" inputmode="numeric" pattern="[0-9]{8}" maxlength="8"
                   placeholder="8 digit" class="{{ $field }}">
        </div>
    </div>
</div>
