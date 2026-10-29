{{-- Tab submenu Laporan + filter bersama --}}
@php
    $input = 'mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100';
@endphp

<div class="space-y-6">
    <nav aria-label="Jenis laporan" class="flex flex-wrap gap-2">
        @foreach($sections as $key => $item)
            <a href="{{ route('admin.reports.show', ['section' => $key] + $filters) }}"
               @if($key === $section) aria-current="page" @endif
               class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition
               {{ $key === $section ? 'border-polmind-blue bg-polmind-blue text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-polmind-blue hover:text-polmind-blue' }}">
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <form action="{{ route('admin.reports.show', $section) }}" method="GET"
          class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-7">
            <div>
                <label for="start_date" class="text-xs font-semibold text-slate-600">{{ $section === 'pembayaran' ? 'Tanggal bayar dari' : 'Tanggal daftar dari' }}</label>
                <input type="date" id="start_date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" class="{{ $input }}">
            </div>
            <div>
                <label for="end_date" class="text-xs font-semibold text-slate-600">Sampai</label>
                <input type="date" id="end_date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" class="{{ $input }}">
            </div>
            <div>
                <label for="admission_wave" class="text-xs font-semibold text-slate-600">Gelombang</label>
                <select id="admission_wave" name="admission_wave" class="{{ $input }}">
                    <option value="">Semua gelombang</option>
                    @foreach($admissionWaves as $wave)
                        <option value="{{ $wave->id }}" @selected((int) ($filters['admission_wave'] ?? 0) === $wave->id)>{{ $wave->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="study_program" class="text-xs font-semibold text-slate-600">Prodi pilihan 1</label>
                <select id="study_program" name="study_program" class="{{ $input }}">
                    <option value="">Semua prodi</option>
                    @foreach($studyPrograms as $program)
                        <option value="{{ $program->id }}" @selected((int) ($filters['study_program'] ?? 0) === $program->id)>{{ $program->code }} - {{ $program->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="class_type" class="text-xs font-semibold text-slate-600">Kelas</label>
                <select id="class_type" name="class_type" class="{{ $input }}">
                    <option value="">Semua kelas</option>
                    @foreach($classTypes as $classType)
                        <option value="{{ $classType->id }}" @selected((int) ($filters['class_type'] ?? 0) === $classType->id)>{{ $classType->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="registration_path" class="text-xs font-semibold text-slate-600">Jalur</label>
                <select id="registration_path" name="registration_path" class="{{ $input }}">
                    <option value="">Semua jalur</option>
                    @foreach(\App\Models\RegistrationPath::ordered() as $path)
                        <option value="{{ $path->code }}" @selected(($filters['registration_path'] ?? '') === $path->code)>{{ $path->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 rounded-lg bg-polmind-blue px-4 py-2.5 text-sm font-semibold text-white">Terapkan</button>
                @if($filters)
                    <a href="{{ route('admin.reports.show', $section) }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Reset</a>
                @endif
            </div>
        </div>
    </form>

    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <p class="text-sm text-slate-600">
            {{ $selectedPmbYear?->name ?? 'Tahun PMB' }}
            @if($filters)
                · <span class="font-semibold text-polmind-blue">{{ count($filters) }} filter aktif</span>
            @endif
        </p>
        <div class="flex flex-wrap items-center gap-2">
            @if($section === 'pembayaran')
                <a href="{{ route('admin.reports.export-payments', $filters) }}"
                   class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                    Export Rekap Pembayaran (Excel)
                </a>
            @endif
            <a href="{{ route('admin.reports.export-applicants', $filters) }}"
               class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:border-polmind-blue hover:text-polmind-blue">
                Export Data Camaba (CSV)
            </a>
            @unless($section === 'pembayaran')
                <span class="rounded-lg border border-dashed border-slate-300 px-4 py-2 text-xs font-semibold text-slate-400" title="Download laporan dalam format PDF dan XLSX akan ditambahkan berikutnya">
                    PDF &amp; XLSX menyusul
                </span>
            @endunless
        </div>
    </div>
</div>
