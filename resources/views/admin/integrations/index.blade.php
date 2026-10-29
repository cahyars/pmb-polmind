@extends('layouts.admin')

@section('title', 'Integrasi SIAKAD')
@section('page_title', 'Integrasi SIAKAD')
@section('page_subtitle', 'Monitoring sinkronisasi data camaba dari PMB ke SIAKAD.')

@section('content')
@php
    $syncLabels = [
        'siap_sinkron' => 'Siap Sinkron',
        'proses_sinkron' => 'Proses Sinkron',
        'sudah_sinkron' => 'Sudah Sinkron',
        'gagal_sinkron' => 'Gagal Sinkron',
    ];

    $syncClasses = [
        'siap_sinkron' => 'bg-purple-100 text-purple-700',
        'proses_sinkron' => 'bg-yellow-100 text-yellow-700',
        'sudah_sinkron' => 'bg-green-100 text-green-700',
        'gagal_sinkron' => 'bg-red-100 text-red-700',
    ];

@endphp

<div class="space-y-8">

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-5 py-4 text-sm font-semibold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            [
                'label' => 'Siap Sinkron',
                'value' => $readySyncApplicants,
                'desc' => 'Sudah ber-NIM, menunggu ditarik',
                'class' => 'text-purple-700',
            ],
            [
                'label' => 'Proses Sinkron',
                'value' => $processingApplicants,
                'desc' => 'Sedang diproses',
                'class' => 'text-yellow-700',
            ],
            [
                'label' => 'Sudah Sinkron',
                'value' => $syncedApplicants,
                'desc' => 'Sudah ditarik SIAKAD',
                'class' => 'text-green-700',
            ],
            [
                'label' => 'Gagal Sinkron',
                'value' => $failedApplicants,
                'desc' => 'Perlu dicek ulang',
                'class' => 'text-red-700',
            ],
        ] as $card)
            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-[28px] font-bold leading-tight {{ $card['class'] }}">{{ $card['value'] }}</p>
                <p class="mt-2 text-xs font-medium text-slate-500">{{ $card['desc'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Integration Flow --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-base font-semibold text-slate-900">Flow Integrasi PMB → SIAKAD</h2>

        <div class="mt-6 grid gap-4 md:grid-cols-4">
            @foreach([
                ['step' => '01', 'title' => 'Daftar Ulang Valid', 'desc' => 'Camaba diterima dan daftar ulang sudah valid.'],
                ['step' => '02', 'title' => 'Siap Sinkron', 'desc' => 'Admin membubuhkan NIM di menu Kelulusan > Generate NIM.'],
                ['step' => '03', 'title' => 'Ditarik SIAKAD', 'desc' => 'SIAKAD menarik data lewat API PMB (status siap atau proses sinkron).'],
                ['step' => '04', 'title' => 'Sudah Sinkron', 'desc' => 'SIAKAD mengirim konfirmasi berhasil, NIM terkunci.'],
            ] as $flow)
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                    <span class="rounded-full bg-polmind-blue px-3 py-1 text-xs font-bold text-white">
                        {{ $flow['step'] }}
                    </span>
                    <h3 class="mt-4 font-bold text-slate-900">{{ $flow['title'] }}</h3>
                    <p class="mt-2 text-xs leading-5 text-slate-500">{{ $flow['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Filter --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="border-b border-slate-200 pb-5">
            <h2 class="text-base font-semibold text-slate-900">Filter Data Sinkron</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Cari berdasarkan nama, nomor pendaftaran, NIK, NISN, NIM, nomor HP, atau asal sekolah.
            </p>
        </div>

        <form action="{{ route('admin.integrations.index') }}" method="GET" class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-6">
            <div class="xl:col-span-2">
                <label class="text-sm font-semibold text-slate-700">Pencarian</label>
                <input type="text"
                       name="keyword"
                       value="{{ request('keyword') }}"
                       placeholder="Nama, no pendaftaran, NIK, NISN, NIM..."
                       class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">Program Studi</label>
                <select name="study_program"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Prodi</option>
                    @foreach($studyPrograms as $program)
                        <option value="{{ $program->id }}" @selected(request('study_program') == $program->id)>
                            {{ $program->code }} - {{ $program->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">Jalur</label>
                <select name="registration_path"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Jalur</option>
                    @foreach(\App\Models\RegistrationPath::ordered() as $pathOption)
                        <option value="{{ $pathOption->code }}" @selected(request('registration_path') === $pathOption->code)>{{ $pathOption->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">Status Sinkron</label>
                <select name="sync_status"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Status</option>
                    @foreach($syncLabels as $key => $label)
                        <option value="{{ $key }}" @selected(request('sync_status') === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-3 xl:col-span-6">
                <button type="submit"
                        class="rounded-lg bg-polmind-blue px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                    Terapkan Filter
                </button>

                <a href="{{ route('admin.integrations.index') }}"
                   class="rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="grid min-w-0 gap-8 xl:grid-cols-[minmax(0,1fr)_360px]">

        {{-- Main Table --}}
        <div class="min-w-0 rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Data Siap Integrasi</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Data ini bersumber dari camaba yang sudah daftar ulang valid dan siap dikirim ke SIAKAD.
                    </p>
                </div>

                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-polmind-blue">
                    {{ $applicants->total() }} Data
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1280px] text-left text-sm">
                    <thead class="border-b border-slate-200 bg-white text-xs text-slate-400 [&_th]:font-medium">
                        <tr>
                            <th class="px-5 py-3.5">No</th>
                            <th class="px-5 py-3.5">Camaba</th>
                            <th class="px-5 py-3.5">Jalur</th>
                            <th class="px-5 py-3.5">Akademik</th>
                            <th class="px-5 py-3.5">Kelengkapan Data</th>
                            <th class="px-5 py-3.5">Daftar Ulang</th>
                            <th class="px-5 py-3.5">Status Sinkron</th>
                            <th class="px-5 py-3.5">Payload</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="table-zebra">
                        @forelse($applicants as $applicant)
                            @php
                                $registrationPath = $applicant->registration_path ?? 'umum';

                                $payload = \App\Services\SiakadPayload::student($applicant);

                                $missingFields = collect(\App\Services\SiakadPayload::missingFields($applicant));

                                $canProcess = in_array($applicant->sync_status, ['siap_sinkron', 'gagal_sinkron'])
                                    && $missingFields->isEmpty()
                                    && filled($applicant->nim);

                                $canSetSuccess = in_array($applicant->sync_status, ['siap_sinkron', 'proses_sinkron']) && filled($applicant->nim);
                                $canSetFailed = in_array($applicant->sync_status, ['siap_sinkron', 'proses_sinkron', 'gagal_sinkron']);
                            @endphp

                            <tr class="align-top transition hover:bg-slate-50">
                                <td class="px-5 py-3.5 text-slate-500">{{ $applicants->firstItem() + $loop->index }}</td>
                                <td class="px-5 py-3.5">
                                    <p class="font-bold text-polmind-blue">
                                        {{ $applicant->registration_number }}
                                    </p>

                                    <p class="mt-1 font-semibold text-slate-800">
                                        {{ $applicant->full_name }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $applicant->email }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $applicant->phone ?? '-' }}
                                    </p>
                                </td>

                                <td class="px-5 py-3.5">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ \App\Models\RegistrationPath::badgeClass($registrationPath) }}">
                                        {{ $applicant->registration_path_label }}
                                    </span>
                                </td>

                                <td class="px-5 py-3.5">
                                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-polmind-blue">
                                        {{ $applicant->enrolled_study_program?->code ?? '-' }}
                                    </span>

                                    <p class="mt-2 text-xs text-slate-500">
                                        {{ $applicant->classType?->name ?? '-' }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $applicant->admissionWave?->name ?? '-' }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $applicant->pmbYear?->name ?? '-' }}
                                    </p>
                                </td>

                                <td class="px-5 py-3.5">
                                    <p class="font-semibold text-slate-700">
                                        NIK: {{ $applicant->nik ?? '-' }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        NISN: {{ $applicant->nisn ?? '-' }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $applicant->education?->school_name ?? '-' }}
                                    </p>

                                    @if($missingFields->isEmpty())
                                        <p class="mt-3 rounded-lg bg-green-50 px-3 py-2 text-xs font-semibold text-green-700">
                                            Data inti lengkap
                                        </p>
                                    @else
                                        <div class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-xs leading-5 text-red-700">
                                            <p class="font-bold">Data kurang:</p>
                                            <p class="mt-1">{{ $missingFields->implode(', ') }}</p>
                                        </div>
                                    @endif
                                </td>

                                <td class="px-5 py-3.5">
                                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-700">
                                        {{ str_replace('_', ' ', ucfirst($applicant->reRegistration?->status ?? '-')) }}
                                    </span>

                                    @if($applicant->reRegistration?->ready_sync_at)
                                        <p class="mt-2 text-xs text-slate-500">
                                            Siap: {{ $applicant->reRegistration->ready_sync_at->format('d M Y H:i') }}
                                        </p>
                                    @endif
                                </td>

                                <td class="px-5 py-3.5">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $syncClasses[$applicant->sync_status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $syncLabels[$applicant->sync_status] ?? $applicant->sync_status }}
                                    </span>

                                    @if($applicant->nim)
                                        <p class="mt-2 text-xs font-bold text-green-700">
                                            NIM: {{ $applicant->nim }}
                                        </p>
                                    @else
                                        <p class="mt-2 text-xs text-slate-500">
                                            NIM belum tersedia
                                        </p>
                                    @endif

                                    @if($applicant->synced_at)
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $applicant->synced_at->format('d M Y H:i') }}
                                        </p>
                                    @endif
                                </td>

                                <td class="px-5 py-3.5">
                                    <script type="application/json" id="payload-{{ $applicant->id }}">
{!! json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}
                                    </script>

                                    <button type="button"
                                            onclick="Swal.fire({
                                                title: 'Payload SIAKAD',
                                                html: '<pre style=&quot;text-align:left;white-space:pre-wrap;font-size:12px;background:#f1f5f9;padding:14px;border-radius:12px;max-height:520px;overflow:auto;&quot;>' + document.getElementById('payload-{{ $applicant->id }}').textContent + '</pre>',
                                                width: 800,
                                                confirmButtonColor: '#003B82'
                                            })"
                                            class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-polmind-blue transition hover:bg-blue-100">
                                        Lihat Payload
                                    </button>
                                </td>

                                <td class="px-5 py-3.5">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ url('/admin/applicants/' . $applicant->registration_number) }}"
                                           class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                            Detail
                                        </a>

                                        @if($canProcess)
                                            <form action="{{ route('admin.integrations.processing', $applicant) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('Tandai data ini sedang proses sinkron ke SIAKAD?')">
                                                @csrf
                                                @method('PATCH')

                                                <button type="submit"
                                                        class="rounded-lg bg-yellow-500 px-3 py-2 text-xs font-bold text-polmind-blue-dark hover:brightness-95">
                                                    Proses
                                                </button>
                                            </form>
                                        @endif

                                        @if($canSetSuccess)
                                            <button type="button"
                                                    onclick="document.getElementById('synced-form-{{ $applicant->id }}').classList.toggle('hidden')"
                                                    class="rounded-lg bg-green-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-green-700">
                                                Sukses
                                            </button>
                                        @endif

                                        @if($canSetFailed)
                                            <button type="button"
                                                    onclick="document.getElementById('failed-form-{{ $applicant->id }}').classList.toggle('hidden')"
                                                    class="rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-red-700">
                                                Gagal
                                            </button>
                                        @endif

                                        @if(! $canProcess && ! $canSetSuccess && ! $canSetFailed)
                                            <span class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-500">
                                                Selesai
                                            </span>
                                        @endif
                                    </div>

                                    @if($canSetSuccess)
                                        <form id="synced-form-{{ $applicant->id }}"
                                              action="{{ route('admin.integrations.synced', $applicant) }}"
                                              method="POST"
                                              class="mt-3 hidden rounded-lg border border-green-200 bg-green-50 p-3">
                                            @csrf
                                            @method('PATCH')

                                            <p class="text-xs leading-5 text-green-800">
                                                Tandai NIM <span class="font-bold">{{ $applicant->nim }}</span> sudah masuk SIAKAD?
                                                Gunakan bila SIAKAD tidak mengirim konfirmasi lewat API. NIM akan terkunci.
                                            </p>

                                            <div class="mt-2 flex justify-end gap-2">
                                                <button type="button"
                                                        onclick="document.getElementById('synced-form-{{ $applicant->id }}').classList.add('hidden')"
                                                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700">
                                                    Batal
                                                </button>

                                                <button type="submit"
                                                        class="rounded-lg bg-green-600 px-3 py-2 text-xs font-semibold text-white">
                                                    Simpan Sukses
                                                </button>
                                            </div>
                                        </form>
                                    @endif

                                    @if($canSetFailed)
                                        <form id="failed-form-{{ $applicant->id }}"
                                              action="{{ route('admin.integrations.failed', $applicant) }}"
                                              method="POST"
                                              class="mt-3 hidden rounded-lg border border-red-200 bg-red-50 p-3">
                                            @csrf
                                            @method('PATCH')

                                            <textarea name="error_message"
                                                      rows="3"
                                                      required
                                                      placeholder="Tulis error sinkronisasi..."
                                                      class="w-full rounded-lg border border-red-200 px-3 py-2 text-xs outline-none focus:border-red-500 focus:ring-4 focus:ring-red-100"></textarea>

                                            <div class="mt-2 flex justify-end gap-2">
                                                <button type="button"
                                                        onclick="document.getElementById('failed-form-{{ $applicant->id }}').classList.add('hidden')"
                                                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700">
                                                    Batal
                                                </button>

                                                <button type="submit"
                                                        class="rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white">
                                                    Simpan Gagal
                                                </button>
                                            </div>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center">
                                    <p class="text-sm font-semibold text-slate-600">
                                        Belum ada data untuk integrasi.
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Data muncul setelah daftar ulang valid dan NIM dibubuhkan di menu Kelulusan > Generate NIM.
                                    </p>
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

        {{-- Latest Logs --}}
        <aside class="min-w-0 space-y-6 overflow-hidden">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Log Integrasi Terbaru</h2>

                <div class="mt-5 space-y-3">
                    @forelse($latestLogs as $log)
                        <div class="min-w-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-xs font-bold text-slate-700">
                                    {{ $log->system_name }} · {{ strtoupper($log->method ?? '-') }}
                                </p>

                                <span class="rounded-full px-3 py-1 text-xs font-bold
                                    {{ $log->status === 'success' ? 'bg-green-100 text-green-700' : '' }}
                                    {{ $log->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                    {{ $log->status === 'failed' ? 'bg-red-100 text-red-700' : '' }}">
                                    {{ $log->status }}
                                </span>
                            </div>

                            <p class="mt-3 text-xs font-semibold text-polmind-blue">
                                {{ $log->applicant?->registration_number ?? '-' }}
                            </p>

                            <p class="mt-1 break-all text-xs text-slate-500">
                                {{ $log->endpoint ?? '-' }}
                            </p>

                            @if($log->error_message)
                                <p class="mt-3 rounded-lg bg-red-50 p-3 text-xs leading-5 text-red-700">
                                    {{ $log->error_message }}
                                </p>
                            @endif

                            @if($log->response_payload)
                                <p class="mt-3 rounded-lg bg-green-50 p-3 text-xs leading-5 text-green-700">
                                    {{ $log->response_payload['message'] ?? 'Response tersimpan.' }}
                                </p>
                            @endif

                            <p class="mt-3 text-xs text-slate-400">
                                {{ $log->created_at?->format('d M Y H:i') }}
                            </p>
                        </div>
                    @empty
                        <p class="text-sm font-semibold text-slate-500">
                            Belum ada log integrasi.
                        </p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-blue-200 bg-blue-50 p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-slate-900">API untuk SIAKAD</h2>
                    @if($apiEnabled)
                        <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-700">Aktif</span>
                    @else
                        <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-bold text-slate-600">Nonaktif</span>
                    @endif
                </div>

                <p class="mt-3 text-sm leading-6 text-slate-700">
                    SIAKAD menarik mahasiswa yang sudah ber-NIM, lalu mengirim konfirmasi per mahasiswa.
                    Akses memakai header <code class="rounded bg-white px-1 text-xs">Authorization: Bearer &lt;token&gt;</code>.
                </p>

                <ul class="mt-4 space-y-2 text-xs">
                    @foreach([
                        ['GET', '/api/siakad/mahasiswa', 'Daftar siap ditarik'],
                        ['GET', '/api/siakad/mahasiswa/{id_pendaftar}', 'Detail satu mahasiswa'],
                        ['POST', '/api/siakad/mahasiswa/{id_pendaftar}/konfirmasi', 'Hasil: berhasil / gagal'],
                    ] as [$method, $path, $label])
                        <li class="rounded-lg bg-white p-3">
                            <p class="font-semibold text-slate-700">{{ $label }}</p>
                            <p class="mt-1 break-all font-mono text-slate-500"><span class="font-bold text-polmind-blue">{{ $method }}</span> {{ url($path) }}</p>
                        </li>
                    @endforeach
                </ul>

                @unless($apiEnabled)
                    <p class="mt-4 text-xs leading-5 text-slate-600">Isi <code>SIAKAD_API_TOKEN</code> di file .env server untuk mengaktifkan API.</p>
                @endunless
            </div>
        </aside>
    </div>

</div>
@endsection