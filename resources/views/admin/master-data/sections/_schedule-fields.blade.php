{{-- Isian satu jadwal tes. $schedule null untuk form tambah. --}}
@php
    $field = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100';
    $label = 'text-xs font-semibold text-slate-600';
    $time = fn (?string $value) => $value ? substr($value, 0, 5) : null;
@endphp

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
    <div class="min-w-0 xl:col-span-2">
        <label class="{{ $label }}">Komponen Tes <span class="text-red-500">*</span></label>
        <select name="selection_component_id" required class="{{ $field }}">
            <option value="">Pilih komponen</option>
            @foreach($selectionComponents as $component)
                <option value="{{ $component->id }}" @selected((string) ($schedule?->selection_component_id) === (string) $component->id)>{{ $component->name }}</option>
            @endforeach
            {{-- Tetap tampilkan komponen nonaktif yang sudah terpakai di jadwal ini. --}}
            @if($schedule && ! $selectionComponents->contains('id', $schedule->selection_component_id) && $schedule->component)
                <option value="{{ $schedule->component->id }}" selected>{{ $schedule->component->name }} (nonaktif)</option>
            @endif
        </select>
    </div>
    <div class="min-w-0">
        <label class="{{ $label }}">Tanggal <span class="text-red-500">*</span></label>
        <input type="date" name="test_date" required value="{{ $schedule?->test_date?->format('Y-m-d') }}" class="{{ $field }}">
    </div>
    <div class="min-w-0">
        <label class="{{ $label }}">Jam Mulai</label>
        <input type="time" name="start_time" value="{{ $time($schedule?->start_time) }}" class="{{ $field }}">
    </div>
    <div class="min-w-0">
        <label class="{{ $label }}">Jam Selesai</label>
        <input type="time" name="end_time" value="{{ $time($schedule?->end_time) }}" class="{{ $field }}">
    </div>
    <div class="min-w-0">
        <label class="{{ $label }}">PIC (Nama Dosen)</label>
        <input type="text" name="pic_name" maxlength="255" value="{{ $schedule?->pic_name }}" placeholder="Contoh: Budi Santoso, M.T." class="{{ $field }}">
    </div>
    <div class="min-w-0 sm:col-span-2 xl:col-span-3">
        <label class="{{ $label }}">Lokasi</label>
        <input type="text" name="location" maxlength="255" value="{{ $schedule?->location }}" placeholder="Contoh: Kampus Polmind Lt. 3 / Online (Zoom)" class="{{ $field }}">
    </div>
    <div class="min-w-0 sm:col-span-2 xl:col-span-3">
        <label class="{{ $label }}">Catatan</label>
        <input type="text" name="notes" maxlength="255" value="{{ $schedule?->notes }}" placeholder="Contoh: Membawa alat tulis dan kartu peserta" class="{{ $field }}">
    </div>
</div>
