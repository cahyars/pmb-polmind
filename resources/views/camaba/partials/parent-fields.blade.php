{{-- Isian orang tua/wali. Variabel: $prefix (father|mother|guardian), $nameRequired, $withSpecialNeed --}}
@php
    $parentData = $applicant->parentData;
    $inputClass = 'mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100';
    $labelClass = 'text-sm font-bold text-slate-700';
    $optionalTag = '<span class="font-semibold text-slate-400">(opsional)</span>';
    $requiredTag = '<span class="text-red-500">*</span>';
    $current = fn (string $suffix) => old($prefix . '_' . $suffix, $parentData?->{$prefix . '_' . $suffix});
@endphp

<div class="grid gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <label for="{{ $prefix }}_name" class="{{ $labelClass }}">Nama Lengkap {!! $nameRequired ? $requiredTag : $optionalTag !!}</label>
        <input id="{{ $prefix }}_name" type="text" name="{{ $prefix }}_name" maxlength="255" @required($nameRequired)
               value="{{ $current('name') }}"
               @if($prefix === 'mother') oninput="const p = this.selectionStart; this.value = this.value.toUpperCase(); this.setSelectionRange(p, p);" @endif
               class="{{ $inputClass }} {{ $prefix === 'mother' ? 'uppercase' : '' }}">
        @if($prefix === 'mother')
            <p class="mt-1 text-xs text-slate-500">Sesuai Kartu Keluarga. Wajib untuk pelaporan PDDikti.</p>
        @endif
    </div>

    <div>
        <label for="{{ $prefix }}_nik" class="{{ $labelClass }}">NIK {!! $optionalTag !!}</label>
        <input id="{{ $prefix }}_nik" type="text" name="{{ $prefix }}_nik" inputmode="numeric" pattern="[0-9]{16}" maxlength="16"
               value="{{ $current('nik') }}" placeholder="16 digit sesuai KK"
               class="{{ $inputClass }}">
    </div>

    <div>
        <label for="{{ $prefix }}_birth_date" class="{{ $labelClass }}">Tanggal Lahir {!! $optionalTag !!}</label>
        <input id="{{ $prefix }}_birth_date" type="date" name="{{ $prefix }}_birth_date" max="{{ now()->subDay()->format('Y-m-d') }}"
               value="{{ old($prefix . '_birth_date', $parentData?->{$prefix . '_birth_date'}?->format('Y-m-d')) }}"
               class="{{ $inputClass }}">
    </div>

    @foreach([
        'education_id' => ['Pendidikan Terakhir', 'pendidikan'],
        'job_id' => ['Pekerjaan', 'pekerjaan'],
        'income_id' => ['Penghasilan per Bulan', 'penghasilan'],
    ] as $suffix => [$fieldLabel, $group])
        <div>
            <label for="{{ $prefix }}_{{ $suffix }}" class="{{ $labelClass }}">{{ $fieldLabel }} {!! $optionalTag !!}</label>
            <select id="{{ $prefix }}_{{ $suffix }}" name="{{ $prefix }}_{{ $suffix }}" class="{{ $inputClass }}">
                <option value="">Pilih</option>
                @foreach($references[$group] as $code => $name)
                    <option value="{{ $code }}" @selected((string) $current($suffix) === (string) $code)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    @endforeach

    <div>
        <label for="{{ $prefix }}_phone" class="{{ $labelClass }}">Nomor HP {!! $optionalTag !!}</label>
        <input id="{{ $prefix }}_phone" type="tel" name="{{ $prefix }}_phone" inputmode="tel" maxlength="16"
               value="{{ $current('phone') }}" placeholder="08xxxxxxxxxx"
               class="{{ $inputClass }}">
    </div>

    @if($withSpecialNeed)
        <div>
            <label for="{{ $prefix }}_special_need_id" class="{{ $labelClass }}">Kebutuhan Khusus <span class="text-red-500">*</span></label>
            <select id="{{ $prefix }}_special_need_id" name="{{ $prefix }}_special_need_id" required class="{{ $inputClass }}">
                @foreach($references['kebutuhan_khusus'] as $code => $name)
                    <option value="{{ $code }}" @selected((string) ($current('special_need_id') ?? 0) === (string) $code)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    @endif
</div>
