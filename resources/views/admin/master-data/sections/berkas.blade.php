{{-- Berkas --}}
<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Jenis Berkas</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Jenis dokumen yang harus atau dapat diupload oleh camaba.
            </p>
        </div>

        <button type="button"
                onclick="document.getElementById('create-document-type-form').classList.toggle('hidden')"
                class="rounded-lg bg-polmind-yellow px-5 py-3 text-sm font-bold text-polmind-blue-dark shadow-sm transition hover:brightness-95">
            Tambah Berkas
        </button>
    </div>

    <form id="create-document-type-form"
        action="{{ route('admin.master-data.document-types.store') }}"
        method="POST"
        class="hidden border-b border-slate-200 bg-slate-50 p-6">
        @csrf

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <input type="text" name="code" placeholder="Kode, contoh SKL" required
                class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <input type="text" name="name" placeholder="Nama berkas" required
                class="xl:col-span-2 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <input type="text" name="allowed_extensions" value="pdf, jpg, jpeg, png" placeholder="pdf, jpg, png"
                class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <input type="number" name="max_size_kb" value="2048" min="1" required
                class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <input type="number" name="sort_order" value="1" min="0" required
                class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <label class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="is_required" value="1" checked>
                Wajib
            </label>

            <label class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="is_active" value="1" checked>
                Aktif
            </label>

            <input type="text" name="description" maxlength="500" placeholder="Keterangan untuk camaba (opsional), contoh: Scan berkas asli dan unggah di sini"
                class="md:col-span-2 xl:col-span-6 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
        </div>

        <div class="mt-4 flex justify-end gap-3">
            <button type="button"
                    onclick="document.getElementById('create-document-type-form').classList.add('hidden')"
                    class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">
                Batal
            </button>

            <button type="submit"
                    class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">
                Simpan Berkas
            </button>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[1100px] text-left text-sm">
            <thead class="border-b border-slate-200 bg-white text-xs text-slate-400 [&_th]:font-medium">
                <tr>
                    <th class="px-5 py-3.5">No</th>
                    <th class="px-5 py-3.5">Urutan</th>
                    <th class="px-5 py-3.5">Kode</th>
                    <th class="px-5 py-3.5">Nama Berkas</th>
                    <th class="px-5 py-3.5">Wajib</th>
                    <th class="px-5 py-3.5">Format</th>
                    <th class="px-5 py-3.5">Max Size</th>
                    <th class="px-5 py-3.5">Aktif</th>
                    <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>

            <tbody class="table-zebra">
                @forelse($documentTypes as $documentType)
                    <tr>
                        <td class="px-5 py-3.5 text-slate-500">{{ $loop->iteration }}</td>
                        <td class="px-5 py-3.5 font-medium text-slate-700">{{ $documentType->sort_order }}</td>
                        <td class="px-5 py-3.5 font-bold text-polmind-blue">{{ $documentType->code }}</td>
                        <td class="px-5 py-3.5 font-medium text-slate-800">
                            {{ $documentType->name }}
                            @if($documentType->description)
                                <p class="mt-0.5 text-xs font-normal text-slate-500">{{ $documentType->description }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            @if($documentType->is_required)
                                <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700">Wajib</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">Opsional</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-slate-600">
                            {{ implode(', ', $documentType->allowed_extensions ?? []) }}
                        </td>
                        <td class="px-5 py-3.5 text-slate-700">
                            {{ number_format($documentType->max_size_kb) }} KB
                        </td>
                        <td class="px-5 py-3.5">
                            @if($documentType->is_active)
                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-700">Aktif</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex justify-end gap-2">
                                <button type="button"
                                        onclick="document.getElementById('edit-document-type-{{ $documentType->id }}').classList.toggle('hidden')"
                                        class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-polmind-blue">
                                    Edit
                                </button>

                                <form action="{{ route('admin.master-data.document-types.toggle', $documentType) }}"
                                    method="POST"
                                    onsubmit="return confirm('Ubah status aktif jenis berkas ini?')">
                                    @csrf
                                    @method('PATCH')

                                    <button type="submit"
                                            class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">
                                        {{ $documentType->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <tr id="edit-document-type-{{ $documentType->id }}" class="hidden bg-slate-50">
                        <td colspan="9" class="px-6 py-5">
                            <form action="{{ route('admin.master-data.document-types.update', $documentType) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                                    <input type="text" name="code" value="{{ $documentType->code }}" required
                                        class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <input type="text" name="name" value="{{ $documentType->name }}" required
                                        class="xl:col-span-2 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <input type="text" name="allowed_extensions" value="{{ implode(', ', $documentType->allowed_extensions ?? []) }}"
                                        class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <input type="number" name="max_size_kb" value="{{ $documentType->max_size_kb }}" min="1" required
                                        class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <input type="number" name="sort_order" value="{{ $documentType->sort_order }}" min="0" required
                                        class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <label class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">
                                        <input type="checkbox" name="is_required" value="1" @checked($documentType->is_required)>
                                        Wajib
                                    </label>

                                    <label class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">
                                        <input type="checkbox" name="is_active" value="1" @checked($documentType->is_active)>
                                        Aktif
                                    </label>

                                    <input type="text" name="description" maxlength="500" value="{{ $documentType->description }}" placeholder="Keterangan untuk camaba (opsional)"
                                        class="md:col-span-2 xl:col-span-6 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                </div>

                                <div class="mt-4 flex justify-end gap-3">
                                    <button type="button"
                                            onclick="document.getElementById('edit-document-type-{{ $documentType->id }}').classList.add('hidden')"
                                            class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">
                                        Batal
                                    </button>

                                    <button type="submit"
                                            class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">
                                        Update Berkas
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-10 text-center text-sm font-medium text-slate-500">
                            Belum ada data jenis berkas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
