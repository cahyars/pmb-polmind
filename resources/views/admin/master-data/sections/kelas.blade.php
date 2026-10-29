{{-- Kelas --}}
<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Jenis Kelas</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Skema kelas yang ditampilkan ke camaba saat registrasi (jadwal dan ketentuan tiap kelas).
            </p>
        </div>

        <button type="button"
                onclick="document.getElementById('create-class-type-form').classList.toggle('hidden')"
                class="rounded-lg bg-polmind-yellow px-5 py-3 text-sm font-bold text-polmind-blue-dark shadow-sm transition hover:brightness-95">
            Tambah Kelas
        </button>
    </div>

    <form id="create-class-type-form"
        action="{{ route('admin.master-data.class-types.store') }}"
        method="POST"
        class="hidden border-b border-slate-200 bg-slate-50 p-6">
        @csrf

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <input type="text" name="code" placeholder="Kode, contoh REG_C" required
                class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <input type="text" name="registration_code" placeholder="Kode nomor pendaftar, contoh C" required maxlength="1"
                class="uppercase rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <input type="text" name="name" placeholder="Nama kelas" required
                class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <label class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="is_active" value="1" checked>
                Aktif
            </label>

            <input type="text" name="description" maxlength="255" placeholder="Ringkasan singkat, contoh: Untuk lulusan baru SMA/SMK"
                class="md:col-span-2 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <input type="text" name="schedule" maxlength="255" placeholder="Jadwal kuliah, contoh: Senin-Jumat, 08.00-16.00"
                class="md:col-span-2 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <textarea name="scheme" rows="4" maxlength="2000" placeholder="Skema kelas, satu poin per baris. Contoh:&#10;Perkuliahan tatap muka di kampus&#10;Magang industri di semester 6"
                    class="md:col-span-2 xl:col-span-4 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100"></textarea>
        </div>

        <div class="mt-4 flex justify-end gap-3">
            <button type="button"
                    onclick="document.getElementById('create-class-type-form').classList.add('hidden')"
                    class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">
                Batal
            </button>

            <button type="submit"
                    class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">
                Simpan Kelas
            </button>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[750px] text-left text-sm">
            <thead class="border-b border-slate-200 bg-white text-xs text-slate-400 [&_th]:font-medium">
                <tr>
                    <th class="px-5 py-3.5">No</th>
                    <th class="px-5 py-3.5">Kode</th>
                    <th class="px-5 py-3.5">Kode No. Pendaftar</th>
                    <th class="px-5 py-3.5">Nama</th>
                    <th class="px-5 py-3.5">Skema</th>
                    <th class="px-5 py-3.5">Aktif</th>
                    <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>

            <tbody class="table-zebra">
                @forelse($classTypes as $classType)
                    <tr>
                        <td class="px-5 py-3.5 text-slate-500">{{ $loop->iteration }}</td>
                        <td class="px-5 py-3.5 font-bold text-polmind-blue">{{ $classType->code }}</td>
                        <td class="px-5 py-3.5 font-bold text-slate-800">{{ $classType->registration_code ?? '-' }}</td>
                        <td class="px-5 py-3.5 font-medium text-slate-800">{{ $classType->name }}</td>
                        <td class="px-5 py-3.5 text-slate-600">
                            <p>{{ $classType->description ?? '-' }}</p>
                            @if($classType->schedule)
                                <p class="mt-1 flex items-center gap-1 text-xs font-semibold text-polmind-blue"><x-icon name="clock" class="h-3.5 w-3.5" stroke-width="2" /> {{ $classType->schedule }}</p>
                            @endif
                            @if($classType->scheme_points)
                                <ul class="mt-1 list-inside list-disc text-xs text-slate-500">
                                    @foreach($classType->scheme_points as $point)
                                        <li>{{ $point }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            @if($classType->is_active)
                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-700">Aktif</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex justify-end gap-2">
                                <button type="button"
                                        onclick="document.getElementById('edit-class-type-{{ $classType->id }}').classList.toggle('hidden')"
                                        class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-polmind-blue">
                                    Edit
                                </button>

                                <form action="{{ route('admin.master-data.class-types.toggle', $classType) }}"
                                    method="POST"
                                    onsubmit="return confirm('Ubah status aktif kelas ini?')">
                                    @csrf
                                    @method('PATCH')

                                    <button type="submit"
                                            class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">
                                        {{ $classType->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <tr id="edit-class-type-{{ $classType->id }}" class="hidden bg-slate-50">
                        <td colspan="7" class="px-6 py-5">
                            <form action="{{ route('admin.master-data.class-types.update', $classType) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <input type="text" name="code" value="{{ $classType->code }}" required
                                        class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <input type="text" name="registration_code" value="{{ $classType->registration_code }}" required maxlength="1"
                                        title="Huruf awal nomor pendaftar"
                                        class="uppercase rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <input type="text" name="name" value="{{ $classType->name }}" required
                                        class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <label class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">
                                        <input type="checkbox" name="is_active" value="1" @checked($classType->is_active)>
                                        Aktif
                                    </label>

                                    <input type="text" name="description" value="{{ $classType->description }}" maxlength="255" placeholder="Ringkasan singkat"
                                        title="Ringkasan singkat kelas"
                                        class="md:col-span-2 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <input type="text" name="schedule" value="{{ $classType->schedule }}" maxlength="255" placeholder="Jadwal kuliah"
                                        title="Jadwal kuliah"
                                        class="md:col-span-2 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <textarea name="scheme" rows="4" maxlength="2000" placeholder="Skema kelas, satu poin per baris"
                                            title="Skema kelas, satu poin per baris"
                                            class="md:col-span-2 xl:col-span-4 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">{{ $classType->scheme }}</textarea>
                                </div>

                                <div class="mt-4 flex justify-end gap-3">
                                    <button type="button"
                                            onclick="document.getElementById('edit-class-type-{{ $classType->id }}').classList.add('hidden')"
                                            class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">
                                        Batal
                                    </button>

                                    <button type="submit"
                                            class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">
                                        Update Kelas
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-sm font-medium text-slate-500">
                            Belum ada data kelas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
