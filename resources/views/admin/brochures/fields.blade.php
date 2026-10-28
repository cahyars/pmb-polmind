{{-- Isian brosur; $brochure null untuk form unggah baru --}}
@php
    $useOld = $brochure ? (int) old('_brochure_id') === $brochure->id : ! old('_brochure_id');
    $fieldValue = fn ($field, $default = null) => $useOld ? old($field, $default) : $default;
@endphp

<div class="md:col-span-2">
    <label class="{{ $label }}">Judul brosur</label>
    <input type="text" name="title" value="{{ $fieldValue('title', $brochure?->title) }}" required maxlength="150"
           placeholder="Contoh: Brosur PMB 2027" class="{{ $input }}">
</div>

<div>
    <label class="{{ $label }}">Urutan tampil</label>
    <input type="number" name="sort_order" value="{{ $fieldValue('sort_order', $brochure?->sort_order ?? 0) }}" min="0" max="999" class="{{ $input }}">
</div>

<div class="md:col-span-3">
    <label class="{{ $label }}">Keterangan <span class="font-semibold text-slate-400">(opsional)</span></label>
    <input type="text" name="description" value="{{ $fieldValue('description', $brochure?->description) }}" maxlength="500"
           placeholder="Contoh: Informasi prodi, jalur masuk, dan rincian biaya" class="{{ $input }}">
</div>

<div class="md:col-span-3">
    <label class="{{ $label }}">File brosur {!! $brochure ? '<span class="font-semibold text-slate-400">(isi jika diganti)</span>' : '' !!}</label>
    <input type="file" name="file" @if(! $brochure) required @endif accept=".pdf,.jpg,.jpeg,.png,.webp"
           class="mt-2 block w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-600 file:mr-4 file:border-0 file:bg-slate-100 file:px-4 file:py-3 file:text-sm file:font-semibold file:text-slate-700">
    <p class="mt-1 text-xs text-slate-500">PDF, JPG, PNG, atau WEBP, maksimal {{ \App\Models\Brochure::MAX_KB / 1024 }} MB.</p>
</div>
