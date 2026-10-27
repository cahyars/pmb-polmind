{{-- Komponen Tes Seleksi --}}
@php
    $field = 'w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100';
@endphp

<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Komponen Tes Seleksi</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Jenis tes yang diikuti camaba. Jadwal tiap komponen diatur di menu Jadwal Seleksi.
            </p>
        </div>

        <button type="button"
                onclick="document.getElementById('create-component-form').classList.toggle('hidden')"
                class="rounded-lg bg-polmind-yellow px-5 py-3 text-sm font-semibold text-polmind-blue-dark shadow-sm transition hover:brightness-95">
            Tambah Komponen
        </button>
    </div>

    <form id="create-component-form"
          action="{{ route('admin.master-data.selection-components.store') }}"
          method="POST"
          class="{{ $errors->any() && old('_form') === 'component-create' ? '' : 'hidden' }} border-b border-slate-200 bg-slate-50 p-6">
        @csrf
        <input type="hidden" name="_form" value="component-create">

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <input type="text" name="code" value="{{ old('code') }}" placeholder="Kode, contoh TES_TULIS" required maxlength="50" class="{{ $field }} uppercase">
            <input type="text" name="name" value="{{ old('name') }}" placeholder="Nama tes, contoh Tes Tertulis" required maxlength="255" class="{{ $field }} xl:col-span-2">
            <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" max="999" placeholder="Urutan" title="Urutan tampil" class="{{ $field }}">
            <textarea name="description" rows="2" maxlength="1000" placeholder="Keterangan singkat (opsional), contoh: materi atau ketentuan tes" class="{{ $field }} md:col-span-2 xl:col-span-3">{{ old('description') }}</textarea>
            <label class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="is_active" value="1" checked>
                Aktif
            </label>
        </div>

        <div class="mt-4 flex justify-end gap-3">
            <button type="button" onclick="document.getElementById('create-component-form').classList.add('hidden')"
                    class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">Batal</button>
            <button type="submit" class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">Simpan Komponen</button>
        </div>
    </form>

    <ul class="divide-y divide-slate-100">
        @forelse($selectionComponents as $component)
            <li x-data="{ editing: false }" class="p-5 sm:px-6">
                <div x-show="! editing" class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-slate-900">{{ $component->name }}</p>
                            <span class="rounded bg-blue-50 px-1.5 py-0.5 text-[11px] font-semibold text-polmind-blue">{{ $component->code }}</span>
                            <span class="rounded px-1.5 py-0.5 text-[11px] font-semibold {{ $component->is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $component->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                        @if($component->description)
                            <p class="mt-1 text-sm text-slate-600">{{ $component->description }}</p>
                        @endif
                        <p class="mt-1 text-xs text-slate-400">Urutan {{ $component->sort_order }} · dipakai di {{ $component->schedules_count }} jadwal</p>
                    </div>

                    <div class="flex shrink-0 gap-2">
                        <button type="button" @click="editing = true"
                                class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-polmind-blue">Edit</button>
                        <form action="{{ route('admin.master-data.selection-components.toggle', $component) }}" method="POST"
                              onsubmit="return confirm('Ubah status aktif komponen tes ini?')">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">
                                {{ $component->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                    </div>
                </div>

                <form x-show="editing" x-cloak action="{{ route('admin.master-data.selection-components.update', $component) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <input type="text" name="code" value="{{ $component->code }}" required maxlength="50" title="Kode" class="{{ $field }} uppercase">
                        <input type="text" name="name" value="{{ $component->name }}" required maxlength="255" title="Nama tes" class="{{ $field }} xl:col-span-2">
                        <input type="number" name="sort_order" value="{{ $component->sort_order }}" min="0" max="999" title="Urutan tampil" class="{{ $field }}">
                        <textarea name="description" rows="2" maxlength="1000" placeholder="Keterangan singkat (opsional)" class="{{ $field }} md:col-span-2 xl:col-span-3">{{ $component->description }}</textarea>
                        <label class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">
                            <input type="checkbox" name="is_active" value="1" @checked($component->is_active)>
                            Aktif
                        </label>
                    </div>

                    <div class="mt-4 flex justify-end gap-3">
                        <button type="button" @click="editing = false" class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">Batal</button>
                        <button type="submit" class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">Update Komponen</button>
                    </div>
                </form>
            </li>
        @empty
            <li class="px-6 py-10 text-center text-sm font-medium text-slate-500">Belum ada komponen tes.</li>
        @endforelse
    </ul>
</div>
