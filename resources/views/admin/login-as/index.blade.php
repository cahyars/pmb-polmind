@extends('layouts.admin')

@section('title', 'Login As')
@section('page_title', 'Login As')
@section('page_subtitle', 'Buka portal camaba persis seperti yang dilihat camaba, tanpa perlu password.')

@section('content')
@php
    $details = $applicant ? [
        'Prodi Pilihan 1' => $applicant->studyProgram ? $applicant->studyProgram->degree . ' - ' . $applicant->studyProgram->name : null,
        'Prodi Pilihan 2' => $applicant->secondStudyProgram ? $applicant->secondStudyProgram->degree . ' - ' . $applicant->secondStudyProgram->name : null,
        'Tahun Akademik' => $applicant->pmbYear?->name,
        'Gelombang' => $applicant->admissionWave?->name,
        'Jalur' => $applicant->registration_path_label,
        'Kelas' => $applicant->classType?->name,
    ] : [];
@endphp

<div class="space-y-6">
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Pencarian --}}
    <form action="{{ route('admin.login-as.index') }}" method="GET" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <label for="applicant" class="text-sm font-semibold text-slate-700">Cari camaba</label>
        <p class="mt-1 text-xs text-slate-500">Ketik nama (awal, tengah, atau akhir) atau ID pendaftar, pilih dari saran, lalu klik Cari.</p>

        <div class="relative mt-3 flex flex-col gap-2 sm:flex-row"
             x-data="loginAsSearch(@js(route('admin.login-as.suggestions')), @js($keyword))"
             @click.outside="open = false">
            <div class="relative min-w-0 flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" stroke-width="2" />
                <input id="applicant" type="text" name="applicant" x-model="q" autocomplete="off" required maxlength="255"
                       @input="suggest()" @focus="suggest()"
                       @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
                       @keydown.enter="if (open && items[active]) { $event.preventDefault(); pick(items[active]) }"
                       @keydown.escape="open = false"
                       placeholder="Contoh: cahya atau 26311033"
                       role="combobox" aria-autocomplete="list" aria-controls="applicant_suggestions" :aria-expanded="open"
                       class="w-full rounded-lg border border-slate-300 py-3 pl-10 pr-4 text-sm outline-none transition focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                <ul id="applicant_suggestions" x-show="open" x-cloak role="listbox"
                    class="absolute inset-x-0 top-full z-20 mt-1 max-h-72 overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                    <template x-for="(item, index) in items" :key="item.id">
                        <li role="option" :aria-selected="index === active"
                            @mousedown.prevent="pick(item)" @mouseenter="active = index"
                            :class="index === active ? 'bg-blue-50 text-polmind-blue' : 'text-slate-700'"
                            class="flex cursor-pointer items-center gap-3 px-4 py-2.5 text-sm">
                            <span class="shrink-0 font-mono text-xs text-slate-500" x-text="item.id"></span>
                            <span class="min-w-0 truncate [&_b]:font-bold [&_b]:text-polmind-blue" x-html="highlight(item.name)"></span>
                        </li>
                    </template>
                </ul>
            </div>

            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-polmind-blue px-6 py-3 text-sm font-semibold text-white transition hover:brightness-110">
                Cari
            </button>
        </div>
    </form>

    {{-- Hasil --}}
    @if($matches->isNotEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold text-slate-700">{{ $matches->count() }} camaba cocok dengan "{{ $keyword }}". Pilih salah satu:</p>
            <ul class="mt-4 divide-y divide-slate-100 rounded-lg border border-slate-200">
                @foreach($matches as $match)
                    <li>
                        <a href="{{ route('admin.login-as.index', ['applicant' => $match->registration_number]) }}"
                           class="flex items-center gap-3 px-4 py-3 text-sm text-slate-700 transition hover:bg-blue-50 hover:text-polmind-blue">
                            <span class="font-mono text-xs text-slate-500">{{ $match->registration_number }}</span>
                            <span class="font-semibold">{{ $match->full_name }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @elseif($applicant)
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-6 md:flex-row">
                <div class="w-28 shrink-0">
                    @if($applicant->photoDocument)
                        <img src="{{ route('admin.documents.file', $applicant->photoDocument) }}"
                             alt="Foto {{ $applicant->full_name }}"
                             class="aspect-[3/4] w-full rounded-lg bg-slate-100 object-cover">
                    @else
                        <div class="flex aspect-[3/4] w-full items-center justify-center rounded-lg bg-polmind-blue text-3xl font-semibold text-white" aria-hidden="true">
                            {{ collect(explode(' ', $applicant->full_name))->filter()->take(2)->map(fn ($word) => mb_substr($word, 0, 1))->implode('') }}
                        </div>
                    @endif
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                        <div class="min-w-0">
                            <p class="font-mono text-sm font-semibold text-slate-500">{{ $applicant->registration_number }}</p>
                            <h2 class="mt-1 text-xl font-bold text-slate-900">{{ $applicant->full_name }}</h2>
                            <a href="{{ route('admin.applicants.show', $applicant->registration_number) }}"
                               class="mt-1 inline-block text-xs font-semibold text-polmind-blue hover:underline">Lihat Detail Camaba</a>
                        </div>

                        <form action="{{ route('admin.login-as.store', $applicant->registration_number) }}" method="POST" target="_blank" class="shrink-0">
                            @csrf
                            <button type="submit"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:brightness-110">
                                Login As
                                <x-icon name="arrow-up-right" class="h-4 w-4" stroke-width="2" />
                            </button>
                        </form>
                    </div>

                    <dl class="mt-5 grid gap-x-8 gap-y-4 border-t border-slate-100 pt-5 sm:grid-cols-2">
                        @foreach($details as $label => $value)
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-800">{{ $value ?: '-' }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    <p class="mt-5 text-xs text-slate-500">Portal camaba terbuka di tab baru. Sesi admin Anda tetap berjalan.</p>
                </div>
            </div>
        </div>
    @else
        <div class="flex flex-col items-center rounded-xl border-2 border-dashed border-slate-200 px-6 py-12 text-center">
            <x-icon name="user" class="h-10 w-10 text-slate-300" stroke-width="1.5" />
            @if($keyword !== '')
                <p class="mt-3 text-sm font-semibold text-slate-700">Camaba "{{ $keyword }}" tidak ditemukan</p>
                <p class="mt-1 text-xs text-slate-500">Pencarian mengikuti Tahun Akademik yang sedang dipilih.</p>
            @else
                <p class="mt-3 text-sm font-semibold text-slate-700">Belum ada camaba dipilih</p>
                <p class="mt-1 text-xs text-slate-500">Detail camaba dan tombol Login As tampil di sini setelah Anda mencari.</p>
            @endif
        </div>
    @endif
</div>

<script>
    window.loginAsSearch = (url, initial) => ({
        q: initial ?? '',
        items: [],
        open: false,
        active: 0,
        timer: null,

        suggest() {
            clearTimeout(this.timer);
            const keyword = this.q.trim();

            // Nilai "ID - NAMA" berarti saran sudah dipilih.
            if (keyword.length < 2 || keyword.includes(' - ')) {
                this.open = false;
                return;
            }

            this.timer = setTimeout(async () => {
                try {
                    const response = await fetch(url + '?q=' + encodeURIComponent(keyword), { headers: { Accept: 'application/json' } });
                    this.items = response.ok ? await response.json() : [];
                } catch (error) {
                    this.items = [];
                }
                this.active = 0;
                this.open = this.items.length > 0;
            }, 200);
        },

        move(step) {
            if (! this.open) return;
            this.active = (this.active + step + this.items.length) % this.items.length;
        },

        pick(item) {
            this.q = item.id + ' - ' + item.name;
            this.open = false;
        },

        highlight(name) {
            const escape = (text) => text.replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
            const keyword = this.q.trim();
            const index = name.toLowerCase().indexOf(keyword.toLowerCase());

            if (index < 0) return escape(name);

            return escape(name.slice(0, index)) + '<b>' + escape(name.slice(index, index + keyword.length)) + '</b>' + escape(name.slice(index + keyword.length));
        },
    });
</script>
@endsection
