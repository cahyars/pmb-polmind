{{-- Ukuran jas almamater. Variabel: $current (ukuran tersimpan). "Lainnya" membuka isian ukuran sendiri. --}}
@php
    // Input lama sudah berisi ukuran akhir (ukuran "Lainnya" diganti tulisan camaba), jadi cukup dipecah lagi.
    [$sizeChoice, $sizeOther] = \App\Services\ApplicantBiodata::almamaterChoice(old('almamater_size', $current));
    $sizeField = 'mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100';
@endphp

<div class="md:col-span-2" x-data="{ size: @js($sizeChoice) }">
    <p class="text-sm font-bold text-slate-700">Ukuran Jas Almamater <span class="text-red-500">*</span></p>
    <div class="mt-2 flex flex-wrap gap-2">
        @foreach([...\App\Models\Applicant::ALMAMATER_SIZES, \App\Services\ApplicantBiodata::ALMAMATER_OTHER] as $size)
            <label class="flex min-w-16 cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700 has-checked:border-polmind-blue has-checked:bg-blue-50 has-checked:text-polmind-blue">
                <input type="radio" name="almamater_size" value="{{ $size }}" x-model="size" required class="sr-only">
                {{ $size }}
            </label>
        @endforeach
    </div>

    <div x-show="size === @js(\App\Services\ApplicantBiodata::ALMAMATER_OTHER)" x-cloak class="mt-3 md:max-w-xs">
        <label for="almamater_size_other" class="text-sm font-bold text-slate-700">Tulis ukuran <span class="text-red-500">*</span></label>
        <input id="almamater_size_other" type="text" name="almamater_size_other" maxlength="10"
               value="{{ $sizeOther }}" placeholder="Contoh: XXXL"
               :required="size === @js(\App\Services\ApplicantBiodata::ALMAMATER_OTHER)"
               oninput="this.value = this.value.toUpperCase()"
               class="{{ $sizeField }} uppercase">
    </div>
    <p class="mt-1 text-xs leading-5 text-slate-500">Dipakai untuk pemesanan jas almamater mahasiswa baru.</p>
</div>
