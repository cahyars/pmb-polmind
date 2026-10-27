{{-- Gelombang --}}
<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Gelombang Pendaftaran</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Gelombang aktif menentukan periode pendaftaran yang sedang dibuka.
            </p>
        </div>

        <button type="button"
                onclick="document.getElementById('create-admission-wave-form').classList.toggle('hidden')"
                class="rounded-lg bg-polmind-yellow px-5 py-3 text-sm font-bold text-polmind-blue-dark shadow-sm transition hover:brightness-95">
            Tambah Gelombang
        </button>
    </div>

    <form id="create-admission-wave-form"
        action="{{ route('admin.master-data.admission-waves.store') }}"
        method="POST"
        class="hidden border-b border-slate-200 bg-slate-50 p-6">
        @csrf

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <select name="pmb_year_id" required
                    class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                <option value="">Pilih Tahun PMB</option>
                @foreach($pmbYears as $year)
                    <option value="{{ $year->id }}" @selected($year->id === ($selectedPmbYear?->id ?? $activePmbYear?->id))>
                        {{ $year->name }}
                    </option>
                @endforeach
            </select>

            <input type="text" name="code" placeholder="Kode, contoh GEL4" required
                class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <input type="text" name="name" placeholder="Nama gelombang" required
                class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <input type="number" name="registration_fee" value="350000" min="0" required
                class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <select name="status" required
                    class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                <option value="draft">Draft</option>
                <option value="open">Open</option>
                <option value="closed">Closed</option>
            </select>

            <label class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="is_active" value="1">
                Aktif
            </label>

            <input type="date" name="start_date"
                class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <input type="date" name="end_date"
                class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
        </div>

        <div class="mt-4 flex justify-end gap-3">
            <button type="button"
                    onclick="document.getElementById('create-admission-wave-form').classList.add('hidden')"
                    class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">
                Batal
            </button>

            <button type="submit"
                    class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">
                Simpan Gelombang
            </button>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[1100px] text-left text-sm">
            <thead class="border-b border-slate-200 bg-white text-xs text-slate-400 [&_th]:font-medium">
                <tr>
                    <th class="px-5 py-3.5">Tahun PMB</th>
                    <th class="px-5 py-3.5">Kode</th>
                    <th class="px-5 py-3.5">Nama</th>
                    <th class="px-5 py-3.5">Periode</th>
                    <th class="px-5 py-3.5">Biaya Daftar</th>
                    <th class="px-5 py-3.5">Status</th>
                    <th class="px-5 py-3.5">Aktif</th>
                    <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>

            <tbody class="table-zebra">
                @forelse($admissionWaves as $wave)
                    <tr>
                        <td class="px-5 py-3.5 font-medium text-slate-800">{{ $wave->pmbYear?->name ?? '-' }}</td>
                        <td class="px-5 py-3.5 font-bold text-polmind-blue">{{ $wave->code }}</td>
                        <td class="px-5 py-3.5 font-medium text-slate-800">{{ $wave->name }}</td>
                        <td class="px-5 py-3.5 text-slate-600">
                            {{ $wave->start_date?->format('d M Y') ?? '-' }}
                            -
                            {{ $wave->end_date?->format('d M Y') ?? '-' }}
                        </td>
                        <td class="px-5 py-3.5 font-bold text-polmind-blue">
                            Rp{{ number_format($wave->registration_fee, 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass($wave->status) }}">
                                {{ ucfirst($wave->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            @if($wave->is_active)
                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-700">Aktif</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">Tidak</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex justify-end gap-2">
                                <button type="button"
                                        onclick="document.getElementById('edit-admission-wave-{{ $wave->id }}').classList.toggle('hidden')"
                                        class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-polmind-blue">
                                    Edit
                                </button>

                                <form action="{{ route('admin.master-data.admission-waves.toggle', $wave) }}"
                                    method="POST"
                                    onsubmit="return confirm('Ubah status aktif gelombang ini?')">
                                    @csrf
                                    @method('PATCH')

                                    <button type="submit"
                                            class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">
                                        {{ $wave->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <tr id="edit-admission-wave-{{ $wave->id }}" class="hidden bg-slate-50">
                        <td colspan="8" class="px-6 py-5">
                            <form action="{{ route('admin.master-data.admission-waves.update', $wave) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                                    <select name="pmb_year_id" required
                                            class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                        @foreach($pmbYears as $year)
                                            <option value="{{ $year->id }}" @selected($wave->pmb_year_id === $year->id)>
                                                {{ $year->name }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <input type="text" name="code" value="{{ $wave->code }}" required
                                        class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <input type="text" name="name" value="{{ $wave->name }}" required
                                        class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <input type="number" name="registration_fee" value="{{ $wave->registration_fee }}" min="0" required
                                        class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <select name="status" required
                                            class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                        @foreach(['draft', 'open', 'closed'] as $status)
                                            <option value="{{ $status }}" @selected($wave->status === $status)>
                                                {{ ucfirst($status) }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <label class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">
                                        <input type="checkbox" name="is_active" value="1" @checked($wave->is_active)>
                                        Aktif
                                    </label>

                                    <input type="date" name="start_date" value="{{ $wave->start_date?->format('Y-m-d') }}"
                                        class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <input type="date" name="end_date" value="{{ $wave->end_date?->format('Y-m-d') }}"
                                        class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                </div>

                                <div class="mt-4 flex justify-end gap-3">
                                    <button type="button"
                                            onclick="document.getElementById('edit-admission-wave-{{ $wave->id }}').classList.add('hidden')"
                                            class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">
                                        Batal
                                    </button>

                                    <button type="submit"
                                            class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">
                                        Update Gelombang
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-sm font-medium text-slate-500">
                            Belum ada data gelombang.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
