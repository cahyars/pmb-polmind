@extends('layouts.admin')

@section('title', 'Pengumuman')
@section('page_title', 'Pengumuman')
@section('page_subtitle', 'Kirim pengumuman ke camaba sesuai kategori. Pengumuman tampil di ikon lonceng portal camaba.')

@section('content')
@php
    $input = 'mt-2 w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100';
    $label = 'text-sm font-semibold text-slate-700';
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

    {{-- Kirim --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
         x-data="announcementForm(@js([
             'open' => $announcements->isEmpty() || $errors->any(),
             'audiences' => old('audiences', []),
             'wave' => (string) old('admission_wave_id', ''),
             'program' => (string) old('study_program_id', ''),
             'countUrl' => route('admin.announcements.recipient-count'),
         ]))">
        <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Buat Pengumuman</h2>
                <p class="mt-1 text-sm text-slate-600">Penerima dikunci saat dikirim: camaba yang masuk kategori setelahnya tidak ikut menerima.</p>
            </div>
            <button type="button" @click="open = ! open"
                    class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white transition hover:bg-polmind-blue-dark"
                    x-text="open ? 'Tutup Form' : '+ Buat Pengumuman'">+ Buat Pengumuman</button>
        </div>

        <form x-show="open" x-cloak action="{{ route('admin.announcements.store') }}" method="POST"
              @submit.prevent="confirmSend($el)"
              class="mt-6 grid gap-6 border-t border-slate-200 pt-6 lg:grid-cols-2">
            @csrf

            <div class="space-y-5">
                <div>
                    <label class="{{ $label }}" for="announcement-title">Judul</label>
                    <input id="announcement-title" type="text" name="title" value="{{ old('title') }}" required maxlength="150"
                           placeholder="Contoh: Jadwal Tes Seleksi Gelombang 2" class="{{ $input }}">
                </div>

                <div>
                    <label class="{{ $label }}" for="announcement-body">Isi pengumuman</label>
                    <textarea id="announcement-body" name="body" rows="8" required maxlength="5000"
                              placeholder="Tulis isi pengumuman. Baris baru akan tetap tampil di portal camaba."
                              class="{{ $input }}">{{ old('body') }}</textarea>
                </div>

                <div class="grid gap-5 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}" for="announcement-link">Link <span class="font-semibold text-slate-400">(opsional)</span></label>
                        <input id="announcement-link" type="url" name="link_url" value="{{ old('link_url') }}" maxlength="500"
                               placeholder="https://..." class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}" for="announcement-link-label">Teks tombol</label>
                        <input id="announcement-link-label" type="text" name="link_label" value="{{ old('link_label') }}" maxlength="60"
                               placeholder="Buka Tautan" class="{{ $input }}">
                    </div>
                </div>
            </div>

            <div class="space-y-5">
                <div>
                    <p class="{{ $label }}">Target penerima</p>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        @foreach($segments as $key => $segment)
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 px-3 py-2.5 transition has-[:checked]:border-polmind-blue has-[:checked]:bg-blue-50"
                                   :class="{{ $key === 'semua' ? 'false' : 'all' }} && 'pointer-events-none opacity-50'">
                                <input type="checkbox" name="audiences[]" value="{{ $key }}" x-model="audiences"
                                       class="mt-0.5 h-4 w-4 rounded border-slate-300 text-polmind-blue focus:ring-polmind-blue">
                                <span>
                                    <span class="block text-sm font-semibold text-slate-800">{{ $segment['label'] }}</span>
                                    <span class="block text-xs text-slate-500">{{ $segment['description'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="{{ $label }}" for="announcement-wave">Gelombang <span class="font-semibold text-slate-400">(opsional)</span></label>
                        <select id="announcement-wave" name="admission_wave_id" x-model="wave" class="{{ $input }}">
                            <option value="">Semua gelombang</option>
                            @foreach($waves as $wave)
                                <option value="{{ $wave->id }}">{{ $wave->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}" for="announcement-program">Program studi <span class="font-semibold text-slate-400">(opsional)</span></label>
                        <select id="announcement-program" name="study_program_id" x-model="program" class="{{ $input }}">
                            <option value="">Semua prodi</option>
                            @foreach($studyPrograms as $studyProgram)
                                <option value="{{ $studyProgram->id }}">{{ $studyProgram->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex flex-col gap-4 rounded-lg bg-slate-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-600">
                        <template x-if="audiences.length === 0"><span>Pilih minimal satu target penerima.</span></template>
                        <template x-if="audiences.length > 0">
                            <span>Akan dikirim ke <span class="text-lg font-bold text-polmind-blue" x-text="loading ? '...' : count.toLocaleString('id-ID')"></span> camaba.</span>
                        </template>
                    </p>
                    <button type="submit" :disabled="loading || count === 0"
                            class="rounded-lg bg-polmind-blue px-6 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark disabled:cursor-not-allowed disabled:opacity-50">
                        Kirim Pengumuman
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Daftar --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] text-left text-sm">
                <thead class="border-b border-slate-200 text-xs text-slate-400 [&_th]:px-5 [&_th]:py-3.5 [&_th]:font-medium">
                    <tr>
                        <th>Tanggal</th>
                        <th>Pengumuman</th>
                        <th>Target</th>
                        <th class="text-right">Penerima</th>
                        <th class="text-right">Dibaca</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="table-zebra">
                    @forelse($announcements as $announcement)
                        <tr>
                            <td class="whitespace-nowrap px-5 py-3.5 text-slate-600">
                                {{ $announcement->created_at->translatedFormat('d/m/Y H.i') }}
                                <p class="mt-0.5 text-xs text-slate-400">{{ $announcement->creator?->name ?? '-' }}</p>
                            </td>
                            <td class="px-5 py-3.5">
                                <a href="{{ route('admin.announcements.show', $announcement) }}" class="font-semibold text-slate-900 hover:text-polmind-blue hover:underline">{{ $announcement->title }}</a>
                                <p class="mt-0.5 line-clamp-1 max-w-md text-xs text-slate-500">{{ $announcement->body }}</p>
                            </td>
                            <td class="max-w-64 px-5 py-3.5 text-xs text-slate-600">{{ $announcement->audience_label }}</td>
                            <td class="px-5 py-3.5 text-right font-semibold text-slate-900">{{ number_format($announcement->recipients_count, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right text-slate-700">
                                {{ number_format($announcement->read_count, 0, ',', '.') }}
                                <span class="text-xs text-slate-400">({{ $announcement->recipients_count ? round($announcement->read_count / $announcement->recipients_count * 100) : 0 }}%)</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.announcements.show', $announcement) }}"
                                       class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-polmind-blue">Detail</a>
                                    <form action="{{ route('admin.announcements.destroy', $announcement) }}" method="POST"
                                          onsubmit="return confirm(@js('Tarik pengumuman ' . $announcement->title . '? Pengumuman hilang dari portal semua penerima.'))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700">Tarik</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm font-medium text-slate-500">
                                Belum ada pengumuman pada Tahun PMB ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $announcements->links() }}
</div>

<script>
    // Pratinjau jumlah penerima setiap target berubah, lalu konfirmasi sebelum dikirim.
    window.announcementForm = (config) => ({
        open: config.open,
        audiences: config.audiences,
        wave: config.wave,
        program: config.program,
        count: 0,
        loading: false,
        get all() {
            return this.audiences.includes('semua');
        },
        init() {
            this.$watch('audiences', () => this.refreshCount());
            this.$watch('wave', () => this.refreshCount());
            this.$watch('program', () => this.refreshCount());
            this.refreshCount();
        },
        async refreshCount() {
            if (this.audiences.length === 0) {
                this.count = 0;
                return;
            }

            const params = new URLSearchParams();
            (this.all ? ['semua'] : this.audiences).forEach((audience) => params.append('audiences[]', audience));
            if (this.wave) params.append('admission_wave_id', this.wave);
            if (this.program) params.append('study_program_id', this.program);

            this.loading = true;
            try {
                const response = await fetch(config.countUrl + '?' + params, { headers: { Accept: 'application/json' } });
                this.count = response.ok ? (await response.json()).count : 0;
            } finally {
                this.loading = false;
            }
        },
        confirmSend(form) {
            window.Swal.fire({
                icon: 'question',
                title: 'Kirim Pengumuman?',
                html: 'Pengumuman akan tampil di portal <b>' + this.count.toLocaleString('id-ID') + ' camaba</b> dan tidak bisa diubah setelah dikirim.',
                showCancelButton: true,
                confirmButtonText: 'Kirim',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#003B82',
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        },
    });
</script>
@endsection
