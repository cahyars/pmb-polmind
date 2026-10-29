@extends('layouts.admin')

@section('title', 'Generate NIM')
@section('page_title', 'Generate NIM')
@section('page_subtitle', 'Pembubuhan NIM mahasiswa baru setelah daftar ulang valid, lalu data siap ditarik SIAKAD.')

@section('content')
@php
    $input = 'mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100';
    $syncBadges = [
        'belum_siap' => ['Belum siap', 'bg-slate-100 text-slate-600'],
        'siap_sinkron' => ['Siap ditarik', 'bg-blue-100 text-polmind-blue'],
        'proses_sinkron' => ['Proses', 'bg-yellow-100 text-yellow-700'],
        'sudah_sinkron' => ['Sudah ditarik', 'bg-green-100 text-green-700'],
        'gagal_sinkron' => ['Gagal', 'bg-red-100 text-red-700'],
    ];
@endphp

<div class="space-y-8">

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-5 py-4 text-sm font-semibold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Alur kelulusan --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['step' => 1, 'label' => 'Lolos Seleksi', 'value' => $stats['accepted'], 'desc' => 'Camaba diterima', 'url' => route('admin.selections.index', ['status' => 'diterima'])],
            ['step' => 2, 'label' => 'Daftar Ulang Valid', 'value' => $stats['reRegistered'], 'desc' => 'Sudah bayar daftar ulang', 'url' => route('admin.re-registrations.index')],
            ['step' => 3, 'label' => 'Sudah Ada NIM', 'value' => $stats['withNim'], 'desc' => $eligible->count() . ' siap diberi NIM', 'url' => route('admin.nim.index', ['status' => 'siap_nim'])],
            ['step' => 4, 'label' => 'Ditarik SIAKAD', 'value' => $stats['synced'], 'desc' => 'Data sudah masuk SIAKAD', 'url' => route('admin.integrations.index')],
        ] as $card)
            <a href="{{ $card['url'] }}" class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                <p class="flex items-center gap-2 text-sm font-semibold text-slate-500">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-polmind-blue text-xs font-bold text-white">{{ $card['step'] }}</span>
                    {{ $card['label'] }}
                </p>
                <p class="mt-3 text-[28px] font-bold leading-tight text-polmind-blue">{{ $card['value'] }}</p>
                <p class="mt-2 text-xs font-medium text-slate-500">{{ $card['desc'] }}</p>
            </a>
        @endforeach
    </div>

    {{-- Filter --}}
    <form action="{{ route('admin.nim.index') }}" method="GET" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="grid gap-x-8 gap-y-5 md:grid-cols-2">
            <div>
                <label for="wave" class="text-sm font-semibold text-slate-700">Gelombang</label>
                <select id="wave" name="wave" class="{{ $input }}">
                    <option value="">Semua gelombang</option>
                    @foreach($waves as $wave)
                        <option value="{{ $wave->id }}" @selected((string) request('wave') === (string) $wave->id)>{{ $wave->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="registration_path" class="text-sm font-semibold text-slate-700">Jalur</label>
                <select id="registration_path" name="registration_path" class="{{ $input }}">
                    <option value="">Semua jalur</option>
                    @foreach(\App\Models\RegistrationPath::ordered() as $pathOption)
                        <option value="{{ $pathOption->code }}" @selected(request('registration_path') === $pathOption->code)>{{ $pathOption->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="class_type" class="text-sm font-semibold text-slate-700">Sistem Kuliah</label>
                <select id="class_type" name="class_type" class="{{ $input }}">
                    <option value="">Semua kelas</option>
                    @foreach($classTypes as $classType)
                        <option value="{{ $classType->id }}" @selected((string) request('class_type') === (string) $classType->id)>{{ $classType->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="study_program" class="text-sm font-semibold text-slate-700">Program Studi</label>
                <select id="study_program" name="study_program" class="{{ $input }}">
                    <option value="">Semua prodi</option>
                    @foreach($studyPrograms as $program)
                        <option value="{{ $program->id }}" @selected((string) request('study_program') === (string) $program->id)>{{ $program->degree }} - {{ $program->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="status" class="text-sm font-semibold text-slate-700">Status</label>
                <select id="status" name="status" class="{{ $input }}">
                    <option value="">Semua status</option>
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="keyword" class="text-sm font-semibold text-slate-700">Cari</label>
                <input id="keyword" type="search" name="keyword" value="{{ request('keyword') }}" placeholder="Nama, ID pendaftar, atau NIM" class="{{ $input }}">
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="rounded-lg bg-polmind-blue px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                Terapkan Filter
            </button>
            <a href="{{ route('admin.nim.index') }}" class="rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Reset
            </a>
        </div>
    </form>

    {{-- Tabel --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 lg:flex-row lg:items-center">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Daftar Kelulusan</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Generate otomatis mengikuti urutan tanggal daftar ulang dengan format
                    <span class="font-mono font-semibold text-slate-800">{{ $nimExample }}</span>
                    · <a href="{{ route('admin.master-data.show', 'penomoran') }}" class="font-semibold text-polmind-blue hover:underline">Atur format</a>.
                    NIM juga bisa diisi manual lewat tombol edit.
                </p>
            </div>

            <form action="{{ route('admin.nim.generate') }}" method="POST" class="shrink-0"
                  onsubmit="return confirm('Bubuhkan NIM ke {{ $eligible->count() }} camaba sesuai urutan tanggal daftar ulang? NIM yang sudah ada tidak diubah.')">
                @csrf
                <button type="submit" @disabled($eligible->isEmpty())
                        class="inline-flex items-center gap-2 rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">
                    <x-icon name="check" class="h-4 w-4" stroke-width="2" />
                    Generate NIM ({{ $eligible->count() }})
                </button>
            </form>
        </div>

        @if($missingNimCode->isNotEmpty())
            <p class="border-b border-yellow-200 bg-yellow-50 px-6 py-3 text-xs leading-5 text-yellow-800">
                Kode NIM prodi {{ $missingNimCode->implode(', ') }} belum diisi, camaba dari prodi tersebut akan dilewati saat generate.
                <a href="{{ route('admin.master-data.show', 'prodi') }}" class="font-semibold underline">Isi di Master Data Program Studi</a>.
            </p>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead class="border-b border-slate-200 bg-white text-xs text-slate-400 [&_th]:font-medium">
                    <tr>
                        <th class="px-5 py-3.5">No</th>
                        <th class="px-5 py-3.5">ID Pendaftar</th>
                        <th class="px-5 py-3.5">Nama</th>
                        <th class="px-5 py-3.5 text-center">L/P</th>
                        <th class="px-5 py-3.5">Prodi Diterima</th>
                        <th class="px-5 py-3.5 text-center">Daftar Ulang?</th>
                        <th class="px-5 py-3.5">NIM</th>
                        <th class="px-5 py-3.5">SIAKAD</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="table-zebra">
                    @forelse($applicants as $applicant)
                        @php
                            $reRegistered = $applicant->re_registration_status === 'daftar_ulang_valid';
                            $locked = in_array($applicant->sync_status, $lockedSyncStatuses, true);
                            $program = $applicant->enrolled_study_program;
                            $sync = $syncBadges[$applicant->sync_status] ?? [str_replace('_', ' ', ucfirst((string) $applicant->sync_status)), 'bg-slate-100 text-slate-600'];
                        @endphp

                        <tr>
                            <td class="px-5 py-3.5 text-slate-500">{{ $applicants->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-3.5 font-semibold text-polmind-blue">
                                <a href="{{ route('admin.applicants.show', $applicant->registration_number) }}" class="hover:underline">{{ $applicant->registration_number }}</a>
                            </td>
                            <td class="px-5 py-3.5 font-semibold text-slate-800">{{ $applicant->full_name }}</td>
                            <td class="px-5 py-3.5 text-center text-slate-700">{{ $applicant->gender ?? '-' }}</td>
                            <td class="px-5 py-3.5 text-slate-700">{{ $program ? $program->degree . ' - ' . $program->name : '-' }}</td>
                            <td class="px-5 py-3.5 text-center">
                                @if($reRegistered)
                                    <x-icon name="check" class="mx-auto h-5 w-5 text-green-600" stroke-width="2.5" />
                                    <span class="sr-only">Sudah</span>
                                @else
                                    <x-icon name="close" class="mx-auto h-5 w-5 text-red-500" stroke-width="2.5" />
                                    <span class="sr-only">Belum</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 font-mono font-bold text-slate-900">{{ $applicant->nim ?? '-' }}</td>
                            <td class="px-5 py-3.5">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $sync[1] }}">{{ $sync[0] }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex justify-end gap-1.5">
                                    @if($reRegistered && ! $applicant->nim && ! $locked)
                                        <form action="{{ route('admin.nim.generate-one', $applicant) }}" method="POST">
                                            @csrf
                                            <button type="submit" title="Generate NIM otomatis"
                                                    class="flex h-8 w-8 items-center justify-center rounded-md bg-green-600 text-white transition hover:bg-green-700">
                                                <span class="sr-only">Generate NIM {{ $applicant->full_name }}</span>
                                                <x-icon name="check" class="h-4 w-4" stroke-width="2" />
                                            </button>
                                        </form>
                                    @endif

                                    @if($reRegistered && ! $locked)
                                        <button type="button" title="Isi NIM manual"
                                                onclick="document.getElementById('nim-form-{{ $applicant->id }}').classList.toggle('hidden')"
                                                class="flex h-8 w-8 items-center justify-center rounded-md bg-polmind-blue text-white transition hover:bg-polmind-blue-dark">
                                            <span class="sr-only">Isi NIM manual {{ $applicant->full_name }}</span>
                                            <x-icon name="pencil" class="h-4 w-4" stroke-width="2" />
                                        </button>
                                    @endif

                                    @if($applicant->nim && ! $locked)
                                        <form action="{{ route('admin.nim.destroy', $applicant) }}" method="POST"
                                              onsubmit="return confirm('Hapus NIM {{ $applicant->nim }} milik {{ e($applicant->full_name) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Hapus NIM"
                                                    class="flex h-8 w-8 items-center justify-center rounded-md bg-amber-500 text-white transition hover:bg-amber-600">
                                                <span class="sr-only">Hapus NIM {{ $applicant->full_name }}</span>
                                                <x-icon name="close" class="h-4 w-4" stroke-width="2" />
                                            </button>
                                        </form>
                                    @endif

                                    @if(! $reRegistered)
                                        <span class="text-xs text-slate-400">Menunggu daftar ulang</span>
                                    @elseif($locked)
                                        <span class="text-xs text-slate-400">Terkunci SIAKAD</span>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        @if($reRegistered && ! $locked)
                            <tr id="nim-form-{{ $applicant->id }}" class="{{ (int) old('_nim_applicant') === $applicant->id ? '' : 'hidden' }} bg-slate-50">
                                <td colspan="9" class="px-6 py-4">
                                    <form action="{{ route('admin.nim.update', $applicant) }}" method="POST" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="_nim_applicant" value="{{ $applicant->id }}">
                                        <label for="nim-{{ $applicant->id }}" class="text-sm font-semibold text-slate-700">NIM manual {{ $applicant->full_name }}</label>
                                        <input id="nim-{{ $applicant->id }}" type="text" name="nim" required maxlength="30"
                                               value="{{ (int) old('_nim_applicant') === $applicant->id ? old('nim') : $applicant->nim }}"
                                               placeholder="Contoh {{ $nimExample }}"
                                               class="w-full rounded-lg border border-slate-300 px-4 py-2.5 font-mono text-sm uppercase outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100 sm:w-56">
                                        <button type="submit" class="rounded-lg bg-polmind-blue px-5 py-2.5 text-sm font-semibold text-white">Simpan NIM</button>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-sm font-medium text-slate-500">
                                Belum ada camaba yang lolos seleksi sesuai filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 p-6">
            {{ $applicants->links() }}
        </div>
    </div>
</div>
@endsection
