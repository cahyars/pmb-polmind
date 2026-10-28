@extends('layouts.admin')

@section('title', 'Brosur')
@section('page_title', 'Brosur')
@section('page_subtitle', 'Brosur PMB yang dapat diunduh pengunjung dan calon pendaftar dari landing page.')

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

    {{-- Unggah --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm" x-data="{ open: @js($brochures->isEmpty() || ($errors->any() && ! old('_brochure_id'))) }">
        <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Unggah Brosur</h2>
                <p class="mt-1 text-sm text-slate-600">Brosur aktif tampil di bagian "Brosur PMB" pada landing page dan dapat diunduh siapa saja.</p>
            </div>
            <button type="button" @click="open = ! open"
                    class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white transition hover:bg-polmind-blue-dark"
                    x-text="open ? 'Tutup Form' : '+ Unggah Brosur'">+ Unggah Brosur</button>
        </div>

        <form x-show="open" x-cloak action="{{ route('admin.brochures.store') }}" method="POST" enctype="multipart/form-data"
              class="mt-6 grid gap-5 border-t border-slate-200 pt-6 md:grid-cols-3">
            @csrf
            @include('admin.brochures.fields', ['brochure' => null])

            <div class="flex justify-end md:col-span-3">
                <button type="submit" class="rounded-lg bg-polmind-blue px-6 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                    Unggah Brosur
                </button>
            </div>
        </form>
    </div>

    {{-- Daftar --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-left text-sm">
                <thead class="border-b border-slate-200 text-xs text-slate-400 [&_th]:px-5 [&_th]:py-3.5 [&_th]:font-medium">
                    <tr>
                        <th>Urutan</th>
                        <th>Brosur</th>
                        <th>File</th>
                        <th class="text-right">Diunduh</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="table-zebra">
                    @forelse($brochures as $brochure)
                        <tr>
                            <td class="px-5 py-3.5 font-semibold text-slate-700">{{ $brochure->sort_order }}</td>
                            <td class="px-5 py-3.5">
                                <p class="font-semibold text-slate-900">{{ $brochure->title }}</p>
                                @if($brochure->description)
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $brochure->description }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <a href="{{ route('admin.brochures.file', $brochure) }}" target="_blank" rel="noopener" class="font-semibold text-polmind-blue hover:underline">
                                    {{ $brochure->extension }} · {{ $brochure->size_label }}
                                </a>
                                <p class="mt-0.5 max-w-56 truncate text-xs text-slate-500" title="{{ $brochure->file_name }}">{{ $brochure->file_name }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-right font-semibold text-slate-900">{{ number_format($brochure->download_count, 0, ',', '.') }}</td>
                            <td class="px-5 py-3.5">
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $brochure->is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $brochure->is_active ? 'Tampil' : 'Disembunyikan' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex justify-end gap-2">
                                    <button type="button"
                                            onclick="document.getElementById('edit-brochure-{{ $brochure->id }}').classList.toggle('hidden')"
                                            class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-polmind-blue">Edit</button>
                                    <form action="{{ route('admin.brochures.toggle', $brochure) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">
                                            {{ $brochure->is_active ? 'Sembunyikan' : 'Tampilkan' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.brochures.destroy', $brochure) }}" method="POST"
                                          onsubmit="return confirm(@js('Hapus brosur ' . $brochure->title . '? File akan dihapus permanen.'))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr id="edit-brochure-{{ $brochure->id }}" class="{{ (int) old('_brochure_id') === $brochure->id ? '' : 'hidden' }} bg-slate-50">
                            <td colspan="6" class="px-6 py-5">
                                <form action="{{ route('admin.brochures.update', $brochure) }}" method="POST" enctype="multipart/form-data" class="grid gap-5 md:grid-cols-3">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="_brochure_id" value="{{ $brochure->id }}">
                                    @include('admin.brochures.fields', ['brochure' => $brochure])
                                    <div class="flex justify-end gap-3 md:col-span-3">
                                        <button type="button"
                                                onclick="document.getElementById('edit-brochure-{{ $brochure->id }}').classList.add('hidden')"
                                                class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">Batal</button>
                                        <button type="submit" class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm font-medium text-slate-500">
                                Belum ada brosur. Unggah brosur pertama agar dapat diunduh dari landing page.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
