@extends('layouts.admin')

@section('title', 'Follow Up Camaba')
@section('page_title', 'Follow Up Camaba')
@section('page_subtitle', 'Kelola tindak lanjut komunikasi dengan calon mahasiswa baru.')

@section('content')
<div class="space-y-8">

    @if(session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-bold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-bold text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-5">
        @foreach([
            [
                'label' => 'Total Camaba',
                'value' => $totalApplicants,
                'desc' => 'Semua pendaftar',
                'class' => 'text-polmind-blue',
            ],
            [
                'label' => 'Belum Dihubungi',
                'value' => $notContactedApplicants,
                'desc' => 'Belum ada catatan follow up',
                'class' => 'text-slate-700',
            ],
            [
                'label' => 'Sudah Dihubungi',
                'value' => $contactedApplicants,
                'desc' => 'Sudah punya riwayat kontak',
                'class' => 'text-green-700',
            ],
            [
                'label' => 'Prioritas Tinggi',
                'value' => $highPriorityFollowUps,
                'desc' => 'Perlu perhatian cepat',
                'class' => 'text-red-700',
            ],
            [
                'label' => 'Follow Up Hari Ini',
                'value' => $todayFollowUps,
                'desc' => 'Jadwal tindak lanjut hari ini',
                'class' => 'text-yellow-700',
            ],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-4xl font-black {{ $card['class'] }}">{{ $card['value'] }}</p>
                <p class="mt-2 text-xs font-medium text-slate-500">{{ $card['desc'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Filter --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="border-b border-slate-200 pb-5">
            <h2 class="text-xl font-black text-polmind-blue">Filter Follow Up</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Cari camaba berdasarkan nama, nomor pendaftaran, nomor HP, NIK, atau asal sekolah.
            </p>
        </div>

        <form action="{{ route('admin.follow-ups.index') }}" method="GET" class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-5">
            <div class="xl:col-span-2">
                <label class="text-sm font-bold text-slate-700">Pencarian</label>
                <input type="text"
                       name="keyword"
                       value="{{ request('keyword') }}"
                       placeholder="Nama, no pendaftaran, HP, NIK, sekolah..."
                       class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Program Studi</label>
                <select name="study_program"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Prodi</option>
                    @foreach($studyPrograms as $program)
                        <option value="{{ $program->id }}" @selected(request('study_program') == $program->id)>
                            {{ $program->code }} - {{ $program->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Status</label>
                <select name="status"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Status</option>
                    @foreach([
                        'belum_dihubungi' => 'Belum Dihubungi',
                        'sudah_dihubungi' => 'Sudah Dihubungi',
                        'tertarik' => 'Tertarik',
                        'ragu_ragu' => 'Ragu-ragu',
                        'menunggu_orang_tua' => 'Menunggu Orang Tua',
                        'akan_daftar_ulang' => 'Akan Daftar Ulang',
                        'tidak_jadi' => 'Tidak Jadi',
                        'tidak_aktif' => 'Tidak Aktif',
                    ] as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-bold text-slate-700">Prioritas</label>
                <select name="priority"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua Prioritas</option>
                    @foreach([
                        'tinggi' => 'Tinggi',
                        'sedang' => 'Sedang',
                        'rendah' => 'Rendah',
                    ] as $key => $label)
                        <option value="{{ $key }}" @selected(request('priority') === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-3 xl:col-span-5">
                <button type="submit"
                        class="rounded-xl bg-polmind-blue px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-polmind-blue-dark">
                    Terapkan Filter
                </button>

                <a href="{{ route('admin.follow-ups.index') }}"
                   class="rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Main Table --}}
    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-black text-polmind-blue">Daftar Follow Up Camaba</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Catatan komunikasi tersimpan di tabel follow_ups.
                </p>
            </div>

            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-polmind-blue">
                {{ $applicants->total() }} Data
            </span>
        </div>

        @php
            $statusLabels = [
                'belum_dihubungi' => 'Belum Dihubungi',
                'sudah_dihubungi' => 'Sudah Dihubungi',
                'tertarik' => 'Tertarik',
                'ragu_ragu' => 'Ragu-ragu',
                'menunggu_orang_tua' => 'Menunggu Orang Tua',
                'akan_daftar_ulang' => 'Akan Daftar Ulang',
                'tidak_jadi' => 'Tidak Jadi',
                'tidak_aktif' => 'Tidak Aktif',
            ];

            $statusClasses = [
                'belum_dihubungi' => 'bg-slate-100 text-slate-600',
                'sudah_dihubungi' => 'bg-blue-100 text-polmind-blue',
                'tertarik' => 'bg-green-100 text-green-700',
                'ragu_ragu' => 'bg-yellow-100 text-yellow-700',
                'menunggu_orang_tua' => 'bg-yellow-100 text-yellow-700',
                'akan_daftar_ulang' => 'bg-purple-100 text-purple-700',
                'tidak_jadi' => 'bg-red-100 text-red-700',
                'tidak_aktif' => 'bg-red-100 text-red-700',
            ];

            $priorityClasses = [
                'tinggi' => 'bg-red-100 text-red-700',
                'sedang' => 'bg-yellow-100 text-yellow-700',
                'rendah' => 'bg-slate-100 text-slate-600',
            ];
        @endphp

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1250px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Camaba</th>
                        <th class="px-6 py-4">Program</th>
                        <th class="px-6 py-4">Kontak</th>
                        <th class="px-6 py-4">Status Terakhir</th>
                        <th class="px-6 py-4">Catatan Terakhir</th>
                        <th class="px-6 py-4">Jadwal Berikutnya</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200">
                    @forelse($applicants as $applicant)
                        @php
                            $latest = $applicant->latestFollowUp;
                            $status = $latest?->status ?? 'belum_dihubungi';
                            $priority = $latest?->priority ?? 'sedang';

                            $rawPhone = preg_replace('/[^0-9]/', '', $applicant->phone ?? '');

                            if (str_starts_with($rawPhone, '0')) {
                                $waPhone = '62' . substr($rawPhone, 1);
                            } elseif (str_starts_with($rawPhone, '8')) {
                                $waPhone = '62' . $rawPhone;
                            } else {
                                $waPhone = $rawPhone;
                            }

                            $waMessage = rawurlencode(
                                'Halo ' . $applicant->full_name . ', kami dari PMB Politeknik Mitra Industri ingin melakukan follow up terkait proses pendaftaran Anda. Mohon konfirmasi kembali ya. Terima kasih.'
                            );
                        @endphp

                        <tr class="transition hover:bg-slate-50">
                            <td class="px-6 py-5">
                                <p class="font-black text-polmind-blue">
                                    {{ $applicant->registration_number }}
                                </p>
                                <p class="mt-1 font-bold text-slate-800">
                                    {{ $applicant->full_name }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $applicant->education?->school_name ?? '-' }}
                                </p>
                            </td>

                            <td class="px-6 py-5">
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-polmind-blue">
                                    {{ $applicant->studyProgram?->code ?? '-' }}
                                </span>
                                <p class="mt-2 text-xs text-slate-500">
                                    {{ $applicant->classType?->name ?? '-' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $applicant->admissionWave?->name ?? '-' }}
                                </p>
                            </td>

                            <td class="px-6 py-5">
                                <p class="font-bold text-slate-800">
                                    {{ $applicant->phone ?? '-' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $applicant->email }}
                                </p>

                                @if($waPhone)
                                    <a href="https://wa.me/{{ $waPhone }}?text={{ $waMessage }}"
                                       target="_blank"
                                       class="mt-3 inline-flex rounded-xl bg-green-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-green-700">
                                        Chat WA
                                    </a>
                                @endif
                            </td>

                            <td class="px-6 py-5">
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $statusClasses[$status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $statusLabels[$status] ?? $status }}
                                </span>

                                <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-black {{ $priorityClasses[$priority] ?? 'bg-slate-100 text-slate-600' }}">
                                    Prioritas {{ ucfirst($priority) }}
                                </span>

                                @if($latest?->contact_method)
                                    <p class="mt-2 text-xs text-slate-500">
                                        Via {{ ucfirst($latest->contact_method) }}
                                    </p>
                                @endif

                                @if($latest?->contacted_at)
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $latest->contacted_at->format('d M Y H:i') }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-6 py-5">
                                @if($latest)
                                    <p class="max-w-[300px] text-sm leading-6 text-slate-700">
                                        {{ $latest->note }}
                                    </p>
                                    <p class="mt-2 text-xs font-semibold text-slate-500">
                                        Petugas: {{ $latest->officer_name ?? '-' }}
                                    </p>
                                @else
                                    <p class="text-sm font-semibold text-slate-500">
                                        Belum ada catatan follow up.
                                    </p>
                                @endif
                            </td>

                            <td class="px-6 py-5">
                                @if($latest?->next_follow_up_date)
                                    <span class="rounded-full bg-yellow-100 px-3 py-1 text-xs font-black text-yellow-700">
                                        {{ $latest->next_follow_up_date->format('d M Y') }}
                                    </span>
                                @else
                                    <span class="text-xs font-bold text-slate-500">
                                        Belum dijadwalkan
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ url('/admin/applicants/' . $applicant->registration_number) }}"
                                       class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                                        Detail
                                    </a>

                                    <button type="button"
                                            onclick="document.getElementById('follow-up-form-{{ $applicant->id }}').classList.toggle('hidden')"
                                            class="rounded-xl bg-polmind-blue px-3 py-2 text-xs font-bold text-white transition hover:bg-polmind-blue-dark">
                                        Catat FU
                                    </button>
                                </div>

                                <form id="follow-up-form-{{ $applicant->id }}"
                                      action="{{ route('admin.follow-ups.store', $applicant) }}"
                                      method="POST"
                                      class="mt-3 hidden rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    @csrf

                                    <div class="grid gap-3 md:grid-cols-2">
                                        <div>
                                            <label class="text-xs font-bold text-slate-600">Status</label>
                                            <select name="status"
                                                    required
                                                    class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-xs outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                                @foreach([
                                                    'sudah_dihubungi' => 'Sudah Dihubungi',
                                                    'tertarik' => 'Tertarik',
                                                    'ragu_ragu' => 'Ragu-ragu',
                                                    'menunggu_orang_tua' => 'Menunggu Orang Tua',
                                                    'akan_daftar_ulang' => 'Akan Daftar Ulang',
                                                    'tidak_jadi' => 'Tidak Jadi',
                                                    'tidak_aktif' => 'Tidak Aktif',
                                                ] as $key => $label)
                                                    <option value="{{ $key }}" @selected($status === $key)>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="text-xs font-bold text-slate-600">Prioritas</label>
                                            <select name="priority"
                                                    required
                                                    class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-xs outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                                @foreach([
                                                    'tinggi' => 'Tinggi',
                                                    'sedang' => 'Sedang',
                                                    'rendah' => 'Rendah',
                                                ] as $key => $label)
                                                    <option value="{{ $key }}" @selected($priority === $key)>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="text-xs font-bold text-slate-600">Metode Kontak</label>
                                            <select name="contact_method"
                                                    required
                                                    class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-xs outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                                @foreach([
                                                    'whatsapp' => 'WhatsApp',
                                                    'phone' => 'Telepon',
                                                    'email' => 'Email',
                                                    'direct' => 'Langsung',
                                                ] as $key => $label)
                                                    <option value="{{ $key }}">
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="text-xs font-bold text-slate-600">Follow Up Berikutnya</label>
                                            <input type="date"
                                                   name="next_follow_up_date"
                                                   value="{{ $latest?->next_follow_up_date?->format('Y-m-d') }}"
                                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-xs outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <label class="text-xs font-bold text-slate-600">Catatan</label>
                                        <textarea name="note"
                                                  rows="4"
                                                  required
                                                  placeholder="Contoh: Sudah dihubungi via WhatsApp, camaba tertarik namun menunggu diskusi dengan orang tua."
                                                  class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-xs outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100"></textarea>
                                    </div>

                                    <div class="mt-3 flex justify-end gap-2">
                                        <button type="button"
                                                onclick="document.getElementById('follow-up-form-{{ $applicant->id }}').classList.add('hidden')"
                                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700">
                                            Batal
                                        </button>

                                        <button type="submit"
                                                class="rounded-lg bg-polmind-blue px-3 py-2 text-xs font-bold text-white">
                                            Simpan Follow Up
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <p class="text-sm font-bold text-slate-600">
                                    Data camaba tidak ditemukan.
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Coba reset filter atau jalankan seeder.
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

</div>
@endsection