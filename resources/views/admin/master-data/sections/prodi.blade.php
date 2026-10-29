{{-- Prodi --}}
<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Program Studi</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Data prodi untuk pilihan pendaftaran camaba. Kode NIM dipakai saat Generate NIM, contoh A pada A20270001.
            </p>
        </div>

        <button type="button"
                onclick="document.getElementById('create-study-program-form').classList.toggle('hidden')"
                class="rounded-lg bg-polmind-yellow px-5 py-3 text-sm font-bold text-polmind-blue-dark shadow-sm transition hover:brightness-95">
            Tambah Prodi
        </button>
    </div>

    <form id="create-study-program-form"
        action="{{ route('admin.master-data.study-programs.store') }}"
        method="POST"
        class="hidden border-b border-slate-200 bg-slate-50 p-6">
        @csrf

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <input type="text" name="code" placeholder="Kode, contoh TRPL" required
                class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <input type="text" name="nim_code" maxlength="5" placeholder="Kode NIM, contoh A" title="Kode prodi pada NIM, contoh A pada A20270001"
                class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100 uppercase">

            <input type="text" name="name" placeholder="Nama prodi" required
                class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <input type="text" name="degree" value="D4" placeholder="Jenjang" required
                class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <input type="number" name="quota" value="40" min="0" placeholder="Kuota" required
                class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <label class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="is_active" value="1" checked>
                Aktif
            </label>

            <textarea name="description" rows="3" placeholder="Deskripsi prodi"
                    class="md:col-span-2 xl:col-span-6 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100"></textarea>
        </div>

        <div class="mt-4 flex justify-end gap-3">
            <button type="button"
                    onclick="document.getElementById('create-study-program-form').classList.add('hidden')"
                    class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">
                Batal
            </button>

            <button type="submit"
                    class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">
                Simpan Prodi
            </button>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="border-b border-slate-200 bg-white text-xs text-slate-400 [&_th]:font-medium">
                <tr>
                    <th class="px-5 py-3.5">No</th>
                    <th class="px-5 py-3.5">Kode</th>
                    <th class="px-5 py-3.5">Kode NIM</th>
                    <th class="px-5 py-3.5">Nama Prodi</th>
                    <th class="px-5 py-3.5">Jenjang</th>
                    <th class="px-5 py-3.5">Kuota</th>
                    <th class="px-5 py-3.5">Aktif</th>
                    <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>

            <tbody class="table-zebra">
                @forelse($studyPrograms as $program)
                    <tr>
                        <td class="px-5 py-3.5 text-slate-500">{{ $loop->iteration }}</td>
                        <td class="px-5 py-3.5 font-bold text-polmind-blue">{{ $program->code }}</td>
                        <td class="px-5 py-3.5 font-mono font-bold text-slate-700">{{ $program->nim_code ?? '-' }}</td>
                        <td class="px-5 py-3.5">
                            <p class="font-semibold text-slate-800">{{ $program->name }}</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">{{ $program->description }}</p>
                        </td>
                        <td class="px-5 py-3.5 font-medium text-slate-700">{{ $program->degree }}</td>
                        <td class="px-5 py-3.5 font-bold text-polmind-blue">{{ $program->quota }}</td>
                        <td class="px-5 py-3.5">
                            @if($program->is_active)
                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-700">Aktif</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex justify-end gap-2">
                                <button type="button"
                                        onclick="document.getElementById('edit-study-program-{{ $program->id }}').classList.toggle('hidden')"
                                        class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-polmind-blue">
                                    Edit
                                </button>

                                <form action="{{ route('admin.master-data.study-programs.toggle', $program) }}"
                                    method="POST"
                                    onsubmit="return confirm('Ubah status aktif prodi ini?')">
                                    @csrf
                                    @method('PATCH')

                                    <button type="submit"
                                            class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">
                                        {{ $program->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <tr id="edit-study-program-{{ $program->id }}" class="hidden bg-slate-50">
                        <td colspan="8" class="px-6 py-5">
                            <form action="{{ route('admin.master-data.study-programs.update', $program) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                                    <input type="text" name="code" value="{{ $program->code }}" required
                                        class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <input type="text" name="nim_code" value="{{ $program->nim_code }}" maxlength="5" placeholder="Kode NIM, contoh A" title="Kode prodi pada NIM, contoh A pada A20270001"
                                        class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100 uppercase">

                                    <input type="text" name="name" value="{{ $program->name }}" required
                                        class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <input type="text" name="degree" value="{{ $program->degree }}" required
                                        class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <input type="number" name="quota" value="{{ $program->quota }}" min="0" required
                                        class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <label class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">
                                        <input type="checkbox" name="is_active" value="1" @checked($program->is_active)>
                                        Aktif
                                    </label>

                                    <textarea name="description" rows="3"
                                            class="md:col-span-2 xl:col-span-6 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">{{ $program->description }}</textarea>
                                </div>

                                <div class="mt-4 flex justify-end gap-3">
                                    <button type="button"
                                            onclick="document.getElementById('edit-study-program-{{ $program->id }}').classList.add('hidden')"
                                            class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">
                                        Batal
                                    </button>

                                    <button type="submit"
                                            class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">
                                        Update Prodi
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-sm font-medium text-slate-500">
                            Belum ada data prodi.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
