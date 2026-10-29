{{-- Jalur Pendaftaran --}}
@php
    $input = 'rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100';
@endphp

<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Jalur Pendaftaran</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Jalur aktif tampil di landing page dan form registrasi camaba. Jalur yang sudah dipakai camaba tidak bisa dihapus, cukup dinonaktifkan.
            </p>
        </div>

        <button type="button"
                onclick="document.getElementById('create-registration-path-form').classList.toggle('hidden')"
                class="rounded-lg bg-polmind-yellow px-5 py-3 text-sm font-bold text-polmind-blue-dark shadow-sm transition hover:brightness-95">
            Tambah Jalur
        </button>
    </div>

    <form id="create-registration-path-form"
        action="{{ route('admin.master-data.registration-paths.store') }}"
        method="POST"
        class="{{ $errors->any() && ! old('_registration_path_id') && old('name') !== null ? '' : 'hidden' }} border-b border-slate-200 bg-slate-50 p-6">
        @csrf

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <input type="text" name="name" value="{{ old('_registration_path_id') ? '' : old('name') }}" placeholder="Nama jalur, contoh: Kerja Sama Industri" required maxlength="100"
                class="md:col-span-2 {{ $input }}">

            <input type="number" name="sort_order" value="{{ old('_registration_path_id') ? '' : old('sort_order', ($registrationPaths->max('sort_order') ?? 0) + 1) }}" min="0" max="999" required
                title="Urutan tampil di landing page dan form registrasi"
                class="{{ $input }}">

            <label class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="is_active" value="1" checked>
                Aktif
            </label>

            <textarea name="description" rows="3" maxlength="500" placeholder="Keterangan untuk camaba, contoh: Untuk karyawan perusahaan mitra yang direkomendasikan perusahaan."
                    class="md:col-span-2 xl:col-span-4 {{ $input }}">{{ old('_registration_path_id') ? '' : old('description') }}</textarea>
        </div>

        <div class="mt-4 flex justify-end gap-3">
            <button type="button"
                    onclick="document.getElementById('create-registration-path-form').classList.add('hidden')"
                    class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">
                Batal
            </button>

            <button type="submit"
                    class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">
                Simpan Jalur
            </button>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="border-b border-slate-200 bg-white text-xs text-slate-400 [&_th]:font-medium">
                <tr>
                    <th class="px-5 py-3.5">No</th>
                    <th class="px-5 py-3.5">Nama Jalur</th>
                    <th class="px-5 py-3.5">Keterangan</th>
                    <th class="px-5 py-3.5">Urutan</th>
                    <th class="px-5 py-3.5 text-right">Pendaftar</th>
                    <th class="px-5 py-3.5">Status</th>
                    <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>

            <tbody class="table-zebra">
                @forelse($registrationPaths as $path)
                    <tr class="align-top">
                        <td class="px-5 py-3.5 text-slate-500">{{ $loop->iteration }}</td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ \App\Models\RegistrationPath::badgeClass($path->code) }}">{{ $path->name }}</span>
                            <p class="mt-1.5 font-mono text-xs text-slate-400" title="Kode jalur, dipakai pada link ?jalur= di landing page">{{ $path->code }}</p>
                        </td>
                        <td class="max-w-md px-5 py-3.5 text-slate-600">{{ $path->description ?? '-' }}</td>
                        <td class="px-5 py-3.5 font-medium text-slate-700">{{ $path->sort_order }}</td>
                        <td class="px-5 py-3.5 text-right font-semibold text-slate-800">{{ number_format($path->applicants_count, 0, ',', '.') }}</td>
                        <td class="px-5 py-3.5">
                            @if($path->is_active)
                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-700">Aktif</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex justify-end gap-2">
                                <button type="button"
                                        onclick="document.getElementById('edit-registration-path-{{ $path->id }}').classList.toggle('hidden')"
                                        class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-polmind-blue">
                                    Edit
                                </button>

                                <form action="{{ route('admin.master-data.registration-paths.toggle', $path) }}"
                                    method="POST"
                                    onsubmit="return confirm('Ubah status aktif jalur ini?')">
                                    @csrf
                                    @method('PATCH')

                                    <button type="submit"
                                            class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">
                                        {{ $path->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>

                                @if($path->applicants_count === 0)
                                    <form action="{{ route('admin.master-data.registration-paths.destroy', $path) }}"
                                          method="POST"
                                          x-data
                                          @submit.prevent="window.Swal.fire({
                                              icon: 'warning',
                                              title: 'Hapus jalur pendaftaran?',
                                              text: @js('Jalur ' . $path->name . ' akan dihapus dan tidak tampil lagi di form registrasi.'),
                                              showCancelButton: true,
                                              confirmButtonText: 'Ya, hapus',
                                              cancelButtonText: 'Batal',
                                              confirmButtonColor: '#dc2626',
                                          }).then((result) => result.isConfirmed && $el.submit())">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100">
                                            Hapus
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>

                    @php
                        $editing = (int) old('_registration_path_id') === $path->id;
                    @endphp
                    <tr id="edit-registration-path-{{ $path->id }}" class="{{ $editing ? '' : 'hidden' }} bg-slate-50">
                        <td colspan="7" class="px-6 py-5">
                            <form action="{{ route('admin.master-data.registration-paths.update', $path) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="_registration_path_id" value="{{ $path->id }}">

                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <input type="text" name="name" value="{{ $editing ? old('name') : $path->name }}" required maxlength="100"
                                        title="Nama jalur"
                                        class="md:col-span-2 {{ $input }}">

                                    <input type="number" name="sort_order" value="{{ $editing ? old('sort_order') : $path->sort_order }}" min="0" max="999" required
                                        title="Urutan tampil"
                                        class="{{ $input }}">

                                    <label class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">
                                        <input type="checkbox" name="is_active" value="1" @checked($editing ? old('is_active') : $path->is_active)>
                                        Aktif
                                    </label>

                                    <textarea name="description" rows="3" maxlength="500" placeholder="Keterangan untuk camaba"
                                            title="Keterangan jalur"
                                            class="md:col-span-2 xl:col-span-4 {{ $input }}">{{ $editing ? old('description') : $path->description }}</textarea>
                                </div>

                                <div class="mt-4 flex justify-end gap-3">
                                    <button type="button"
                                            onclick="document.getElementById('edit-registration-path-{{ $path->id }}').classList.add('hidden')"
                                            class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">
                                        Batal
                                    </button>

                                    <button type="submit"
                                            class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">
                                        Update Jalur
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-sm font-medium text-slate-500">
                            Belum ada jalur pendaftaran.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
