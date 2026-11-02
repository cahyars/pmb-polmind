@extends('layouts.admin')

@section('title', 'Sumber Informasi')
@section('page_title', 'Sumber Informasi')
@section('page_subtitle', 'Pilihan "Sumber Informasi" di form registrasi online dan pendaftaran di kampus, untuk memantau kanal promosi PMB.')

@section('content')
@php
    $input = 'mt-2 w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100';
    $label = 'text-sm font-semibold text-slate-700';
    $number = fn ($value) => number_format($value, 0, ',', '.');
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

    {{-- Tambah --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm" x-data="{ open: @js($errors->any() && ! old('_source_id')) }">
        <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Tambah Sumber Informasi</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Sumber aktif langsung muncul sebagai pilihan bagi camaba. Pantau hasilnya di
                    <a href="{{ route('admin.reports.show', 'sumber-informasi') }}" class="font-semibold text-polmind-blue hover:underline">Laporan Sumber Informasi</a>.
                </p>
            </div>
            <button type="button" @click="open = ! open"
                    class="shrink-0 rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white transition hover:bg-polmind-blue-dark"
                    x-text="open ? 'Tutup Form' : '+ Tambah Sumber'">+ Tambah Sumber</button>
        </div>

        <form x-show="open" x-cloak action="{{ route('admin.information-sources.store') }}" method="POST"
              class="mt-6 grid gap-5 border-t border-slate-200 pt-6 md:grid-cols-3">
            @csrf
            @include('admin.information-sources.fields', ['source' => null])

            <div class="flex justify-end md:col-span-3">
                <button type="submit" class="rounded-lg bg-polmind-blue px-6 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                    Simpan Sumber
                </button>
            </div>
        </form>
    </div>

    {{-- Daftar --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-6">
            <h2 class="text-base font-semibold text-slate-900">Daftar Sumber Informasi</h2>
            <p class="mt-1 text-sm text-slate-600">
                Jumlah pendaftar untuk {{ $selectedPmbYear?->name ?? 'Tahun PMB terpilih' }}.
                Mengganti nama ikut memperbarui data camaba yang sudah memilihnya.
                @if($notFilledCount > 0)
                    <a href="{{ route('admin.applicants.index', ['source' => \App\Models\InformationSource::NOT_FILLED_KEY]) }}#daftar-camaba" class="font-semibold text-polmind-blue hover:underline">
                        {{ $number($notFilledCount) }} camaba tidak mengisi.
                    </a>
                @endif
            </p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="border-b border-slate-200 text-xs text-slate-400 [&_th]:px-5 [&_th]:py-3.5 [&_th]:font-medium">
                    <tr>
                        <th>Urutan</th>
                        <th>Sumber Informasi</th>
                        <th class="text-right">Pendaftar</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="table-zebra">
                    @forelse($sources as $source)
                        <tr>
                            <td class="px-5 py-3.5 font-semibold text-slate-700">{{ $source->sort_order }}</td>
                            <td class="px-5 py-3.5 font-semibold text-slate-900">{{ $source->name }}</td>
                            <td class="px-5 py-3.5 text-right">
                                @if($source->applicants_count > 0)
                                    <a href="{{ route('admin.applicants.index', ['source' => $source->name]) }}#daftar-camaba" class="font-semibold text-polmind-blue hover:underline">
                                        {{ $number($source->applicants_count) }}
                                    </a>
                                @else
                                    <span class="text-slate-400">0</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $source->is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $source->is_active ? 'Tampil di form' : 'Disembunyikan' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex justify-end gap-2">
                                    <button type="button"
                                            onclick="document.getElementById('edit-source-{{ $source->id }}').classList.toggle('hidden')"
                                            class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-polmind-blue">Edit</button>
                                    <form action="{{ route('admin.information-sources.toggle', $source) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">
                                            {{ $source->is_active ? 'Sembunyikan' : 'Tampilkan' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.information-sources.destroy', $source) }}" method="POST"
                                          onsubmit="return confirm(@js('Hapus sumber informasi ' . $source->name . '?'))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr id="edit-source-{{ $source->id }}" class="{{ (int) old('_source_id') === $source->id ? '' : 'hidden' }} bg-slate-50">
                            <td colspan="5" class="px-6 py-5">
                                <form action="{{ route('admin.information-sources.update', $source) }}" method="POST" class="grid gap-5 md:grid-cols-3">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="_source_id" value="{{ $source->id }}">
                                    @include('admin.information-sources.fields', ['source' => $source])
                                    <div class="flex justify-end gap-3 md:col-span-3">
                                        <button type="button"
                                                onclick="document.getElementById('edit-source-{{ $source->id }}').classList.add('hidden')"
                                                class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">Batal</button>
                                        <button type="submit" class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm font-medium text-slate-500">
                                Belum ada sumber informasi. Tambahkan agar camaba bisa memilih dari mana mereka tahu PMB Polmind.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
