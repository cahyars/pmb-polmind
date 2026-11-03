@extends('layouts.admin')

@section('title', 'Kelompok Tes Seleksi')
@section('page_title', 'Kelompok Tes Seleksi')
@section('page_subtitle', 'Seleksi · Bagi peserta ke kelompok tes dan atur tes yang diikuti tiap kelompok.')

@section('content')
@php
    $input = 'mt-2 w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100';
    $filterInput = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-polmind-blue';
    $label = 'text-sm font-semibold text-slate-700';

    // Warna kelompok mengikuti spreadsheet pembagian kelompok; kelompok mitra selalu ungu.
    $palette = ['bg-orange-100 text-orange-800', 'bg-green-100 text-green-800', 'bg-pink-100 text-pink-800', 'bg-sky-100 text-sky-800', 'bg-amber-100 text-amber-800', 'bg-teal-100 text-teal-800'];
    $groupClass = $groups->where('is_partner', false)->values()
        ->mapWithKeys(fn ($group, $index) => [$group->id => $palette[$index % count($palette)]])
        ->all() + $groups->where('is_partner', true)->mapWithKeys(fn ($group) => [$group->id => 'bg-purple-100 text-purple-700'])->all();
@endphp

<div class="space-y-6">
    @include('admin.spmb.partials.header')

    @if($selectedWave)
        @if($unnumbered)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
                <span class="font-semibold">{{ $unnumbered }} camaba {{ $selectedWave->name }} sudah bayar formulir tetapi belum bernomor peserta</span>, sehingga belum bisa dikelompokkan.
                <a href="{{ route('admin.spmb.numbers.index', ['gelombang' => $selectedWave->id]) }}" class="font-semibold text-polmind-blue underline">Beri nomor peserta</a>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['label' => 'Peserta bernomor', 'value' => $totalParticipants, 'desc' => 'Siap dibagi ke kelompok', 'class' => 'text-polmind-blue'],
                ['label' => 'Belum berkelompok', 'value' => $ungrouped, 'desc' => 'Gunakan Bagi Otomatis atau pindahkan manual', 'class' => $ungrouped ? 'text-amber-700' : 'text-slate-900'],
                ['label' => 'Pendaftar mitra', 'value' => $partnerCount, 'desc' => 'SMK Mitra Industri & Ananda Mitra Industri', 'class' => 'text-purple-700'],
                ['label' => 'Jumlah kelompok', 'value' => $groups->count(), 'desc' => 'TPA 1 sesi bersama, tes lain bergiliran', 'class' => 'text-slate-900'],
            ] as $card)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                    <p class="mt-2 text-2xl font-bold tabular-nums {{ $card['class'] }}">{{ $card['value'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $card['desc'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Kelompok --}}
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm" x-data="{ adding: false }">
            <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 lg:flex-row lg:items-center">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Kelompok {{ $selectedWave->name }}</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600">
                        Bagi Ulang Semua membagi peserta umum ke kelompok dalam blok nomor yang sama besar; pendaftar mitra masuk kelompok mitra.
                    </p>
                </div>

                @if($groups->isNotEmpty())
                    <div class="flex shrink-0 flex-wrap gap-2">
                        <form action="{{ route('admin.spmb.groups.distribute', $selectedWave) }}" method="POST">
                            @csrf
                            <input type="hidden" name="mode" value="belum">
                            <button type="submit" @disabled(! $ungrouped)
                                    class="rounded-lg bg-polmind-blue px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-50">
                                Bagi yang Belum ({{ $ungrouped }})
                            </button>
                        </form>

                        <form action="{{ route('admin.spmb.groups.distribute', $selectedWave) }}" method="POST"
                              x-data
                              @submit.prevent="window.Swal.fire({
                                  icon: 'warning',
                                  title: 'Bagi ulang semua peserta?',
                                  text: @js('Seluruh peserta ' . $selectedWave->name . ' dibagi ulang berurutan nomor peserta. Pindahan manual yang sudah dibuat akan tertimpa.'),
                                  showCancelButton: true,
                                  confirmButtonText: 'Ya, bagi ulang',
                                  cancelButtonText: 'Batal',
                                  confirmButtonColor: '#003B82',
                              }).then((result) => result.isConfirmed && $el.submit())">
                            @csrf
                            <input type="hidden" name="mode" value="semua">
                            <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Bagi Ulang Semua
                            </button>
                        </form>

                        <button type="button" @click="adding = ! adding"
                                class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-polmind-blue"
                                x-text="adding ? 'Tutup Form' : '+ Tambah Kelompok'">+ Tambah Kelompok</button>
                    </div>
                @endif
            </div>

            @if($groups->isEmpty())
                <div class="p-10 text-center">
                    <p class="font-semibold text-slate-800">Belum ada kelompok untuk {{ $selectedWave->name }}.</p>
                    <p class="mx-auto mt-1 max-w-xl text-sm text-slate-600">
                        Kelompok standar: Kelompok 1, 2, dan 3 mengikuti semua tes aktif, ditambah Kelompok Mitra yang hanya MCU. Nama, tes, dan jumlah kelompok bisa diubah setelahnya.
                    </p>
                    <div class="mt-5 flex flex-wrap justify-center gap-2">
                        <form action="{{ route('admin.spmb.groups.defaults', $selectedWave) }}" method="POST">
                            @csrf
                            <button type="submit" class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white hover:brightness-110">Buat Kelompok Standar</button>
                        </form>
                        <button type="button" @click="adding = ! adding" class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Buat Manual</button>
                    </div>
                </div>
            @endif

            <form x-show="adding" x-cloak action="{{ route('admin.spmb.groups.store', $selectedWave) }}" method="POST" class="grid gap-5 border-b border-slate-200 bg-slate-50 p-6 md:grid-cols-2">
                @csrf
                @include('admin.spmb.partials.group-fields', ['group' => null])
                <div class="flex justify-end md:col-span-2">
                    <button type="submit" class="rounded-lg bg-polmind-blue px-6 py-3 text-sm font-bold text-white hover:bg-polmind-blue-dark">Simpan Kelompok</button>
                </div>
            </form>

            @if($groups->isNotEmpty())
                <div class="grid gap-4 p-6 md:grid-cols-2 xl:grid-cols-4">
                    @foreach($groups as $group)
                        <div class="flex flex-col rounded-xl border border-slate-200 p-4" x-data="{ editing: false }">
                            <div class="flex items-start justify-between gap-2">
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $groupClass[$group->id] }}">{{ $group->name }}</span>
                                <span class="text-2xl font-bold tabular-nums text-slate-900">{{ $group->applicants_count }}</span>
                            </div>
                            <p class="mt-1 text-right text-xs text-slate-500">peserta</p>
                            @if($group->notes)
                                <p class="mt-2 text-xs leading-5 text-slate-600">{{ $group->notes }}</p>
                            @endif
                            <div class="mt-3 flex flex-wrap gap-1">
                                @forelse($group->components as $component)
                                    <span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700">{{ $component->name }}</span>
                                @empty
                                    <span class="text-xs text-red-600">Belum ada tes</span>
                                @endforelse
                            </div>
                            <div class="mt-auto flex gap-2 pt-4">
                                <a href="{{ route('admin.spmb.groups.index', ['gelombang' => $selectedWave->id, 'kelompok' => $group->id]) }}"
                                   class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-center text-xs font-semibold text-slate-700 hover:border-polmind-blue hover:text-polmind-blue">Anggota</a>
                                <button type="button" @click="editing = true" class="flex-1 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-polmind-blue">Edit</button>
                                <form action="{{ route('admin.spmb.groups.destroy', $group) }}" method="POST"
                                      onsubmit="return confirm(@js('Hapus ' . $group->name . '? Anggotanya menjadi belum berkelompok.'))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Hapus kelompok" class="rounded-lg border border-red-200 px-2.5 py-2 text-red-600 hover:bg-red-50">
                                        <x-icon name="trash" class="h-4 w-4" stroke-width="1.8" />
                                    </button>
                                </form>
                            </div>

                            {{-- Edit kelompok --}}
                            <div x-show="editing" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" @keydown.escape.window="editing = false">
                                <form action="{{ route('admin.spmb.groups.update', $group) }}" method="POST" @click.outside="editing = false"
                                      class="grid max-h-[90vh] w-full max-w-2xl gap-5 overflow-y-auto rounded-xl bg-white p-6 shadow-xl md:grid-cols-2">
                                    @csrf
                                    @method('PUT')
                                    <h3 class="text-base font-semibold text-slate-900 md:col-span-2">Edit {{ $group->name }}</h3>
                                    @include('admin.spmb.partials.group-fields', ['group' => $group])
                                    <div class="flex justify-end gap-3 md:col-span-2">
                                        <button type="button" @click="editing = false" class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">Batal</button>
                                        <button type="submit" class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Peserta --}}
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-6">
                <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Peserta {{ $selectedWave->name }}</h2>
                        <p class="mt-1 text-sm text-slate-600">Centang peserta lalu pilih kelompok tujuan untuk memindahkan secara manual.</p>
                    </div>
                    <a href="{{ route('admin.spmb.prints.index', ['gelombang' => $selectedWave->id]) }}"
                       class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-polmind-yellow px-4 py-2.5 text-sm font-bold text-polmind-blue-dark hover:brightness-95">
                        <x-icon name="printer" class="h-4 w-4" stroke-width="2" />
                        Cetak Dokumen Tes
                    </a>
                </div>

                <form method="GET" action="{{ route('admin.spmb.groups.index') }}" class="mt-5 grid gap-3 md:grid-cols-4">
                    <input type="hidden" name="gelombang" value="{{ $selectedWave->id }}">
                    <div>
                        <label for="filter-kelompok" class="text-xs font-semibold text-slate-600">Kelompok</label>
                        <select id="filter-kelompok" name="kelompok" class="{{ $filterInput }}">
                            <option value="">Semua kelompok</option>
                            <option value="belum" @selected(($filters['kelompok'] ?? '') === 'belum')>Belum berkelompok</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}" @selected(($filters['kelompok'] ?? '') === (string) $group->id)>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="filter-jenis" class="text-xs font-semibold text-slate-600">Asal pendaftar</label>
                        <select id="filter-jenis" name="jenis" class="{{ $filterInput }}">
                            <option value="">Semua</option>
                            <option value="umum" @selected(($filters['jenis'] ?? '') === 'umum')>Umum</option>
                            <option value="mitra" @selected(($filters['jenis'] ?? '') === 'mitra')>SMK Mitra Industri</option>
                        </select>
                    </div>
                    <div>
                        <label for="filter-keyword" class="text-xs font-semibold text-slate-600">Nama</label>
                        <input id="filter-keyword" type="search" name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="Cari nama peserta" class="{{ $filterInput }}">
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 rounded-lg bg-polmind-blue px-4 py-2.5 text-sm font-semibold text-white">Terapkan</button>
                        <a href="{{ route('admin.spmb.groups.index', ['gelombang' => $selectedWave->id]) }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Reset</a>
                    </div>
                </form>
            </div>

            <form action="{{ route('admin.spmb.groups.move', $selectedWave) }}" method="POST"
                  x-data="{ selected: [], all: @js($participants->pluck('id')) }">
                @csrf
                @method('PATCH')
                @foreach(['kelompok', 'jenis', 'keyword'] as $filter)
                    @if(filled($filters[$filter] ?? null))
                        <input type="hidden" name="{{ $filter }}" value="{{ $filters[$filter] }}">
                    @endif
                @endforeach

                @if($groups->isNotEmpty() && $participants->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-3 border-b border-slate-200 bg-slate-50 px-6 py-3">
                        <span class="text-sm text-slate-600"><span class="font-bold text-slate-900" x-text="selected.length">0</span> dipilih</span>
                        <select name="target" required class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                            <option value="">Pindahkan ke…</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                            <option value="none">Lepas dari kelompok</option>
                        </select>
                        <button type="submit" :disabled="selected.length === 0"
                                class="rounded-lg bg-polmind-blue px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">Pindahkan</button>
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[860px] text-left text-sm">
                        <thead class="border-b border-slate-200 text-xs text-slate-400 [&_th]:px-5 [&_th]:py-3 [&_th]:font-medium">
                            <tr>
                                <th class="w-10">
                                    <input type="checkbox" aria-label="Pilih semua"
                                           :checked="all.length && selected.length === all.length"
                                           @change="selected = $event.target.checked ? [...all] : []"
                                           class="rounded border-slate-300 text-polmind-blue">
                                </th>
                                <th class="w-28">No. Peserta</th>
                                <th>Nama</th>
                                <th>Asal Sekolah</th>
                                <th>Program Studi</th>
                                <th>Kelompok</th>
                            </tr>
                        </thead>
                        <tbody class="table-zebra">
                            @forelse($participants as $applicant)
                                <tr>
                                    <td class="px-5 py-3">
                                        <input type="checkbox" name="applicants[]" value="{{ $applicant->id }}" x-model.number="selected"
                                               aria-label="Pilih {{ $applicant->full_name }}" class="rounded border-slate-300 text-polmind-blue">
                                    </td>
                                    <td class="px-5 py-3 text-base font-bold tabular-nums text-polmind-blue">{{ $applicant->selection_number }}</td>
                                    <td class="px-5 py-3 font-semibold text-slate-800">{{ $applicant->full_name }}</td>
                                    <td class="px-5 py-3 text-slate-600">
                                        {{ $applicant->education?->school_name ?? '-' }}
                                        @if(\App\Services\SelectionGroups::isPartner($applicant))
                                            <span class="ml-1 rounded-full bg-purple-100 px-2 py-0.5 text-[11px] font-bold text-purple-700">Mitra</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-slate-600">{{ $applicant->studyProgram?->code ?? '-' }}</td>
                                    <td class="px-5 py-3">
                                        @if($applicant->selectionGroup)
                                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $groupClass[$applicant->selection_group_id] ?? 'bg-slate-100 text-slate-700' }}">{{ $applicant->selectionGroup->name }}</span>
                                        @else
                                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">Belum</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">Tidak ada peserta bernomor yang sesuai.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
        </section>
    @endif
</div>
@endsection
