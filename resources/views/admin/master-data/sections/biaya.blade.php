{{-- Biaya --}}
<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Komponen Biaya</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Komponen biaya untuk invoice pendaftaran dan daftar ulang.
            </p>
        </div>

        <button type="button"
                onclick="document.getElementById('create-fee-component-form').classList.toggle('hidden')"
                class="rounded-lg bg-polmind-yellow px-5 py-3 text-sm font-bold text-polmind-blue-dark shadow-sm transition hover:brightness-95">
            Tambah Biaya
        </button>
    </div>

    <form id="create-fee-component-form"
        action="{{ route('admin.master-data.fee-components.store') }}"
        method="POST"
        class="hidden border-b border-slate-200 bg-slate-50 p-6">
        @csrf

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <input type="text" name="code" placeholder="Kode, contoh SPP1" required
                class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <input type="text" name="name" placeholder="Nama biaya" required
                class="xl:col-span-2 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <select name="type" required
                    class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                <option value="registration">Pendaftaran</option>
                <option value="re_registration">Daftar Ulang</option>
            </select>

            <input type="number" name="amount" value="0" min="0" required
                class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <input type="number" name="sort_order" value="1" min="0" required
                class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

            <label class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="is_active" value="1" checked>
                Aktif
            </label>

            <label class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 xl:col-span-2"
                   title="Khusus komponen daftar ulang, contoh UKT dan SPI">
                <input type="checkbox" name="is_installable" value="1">
                Bisa dicicil (diaktifkan admin per camaba)
            </label>
        </div>

        <div class="mt-4 flex justify-end gap-3">
            <button type="button"
                    onclick="document.getElementById('create-fee-component-form').classList.add('hidden')"
                    class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">
                Batal
            </button>

            <button type="submit"
                    class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">
                Simpan Biaya
            </button>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[1000px] text-left text-sm">
            <thead class="border-b border-slate-200 bg-white text-xs text-slate-400 [&_th]:font-medium">
                <tr>
                    <th class="px-5 py-3.5">No</th>
                    <th class="px-5 py-3.5">Urutan</th>
                    <th class="px-5 py-3.5">Kode</th>
                    <th class="px-5 py-3.5">Nama Biaya</th>
                    <th class="px-5 py-3.5">Tipe</th>
                    <th class="px-5 py-3.5">Nominal</th>
                    <th class="px-5 py-3.5">Aktif</th>
                    <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>

            <tbody class="table-zebra">
                @forelse($feeComponents as $fee)
                    <tr>
                        <td class="px-5 py-3.5 text-slate-500">{{ $loop->iteration }}</td>
                        <td class="px-5 py-3.5 font-medium text-slate-700">{{ $fee->sort_order }}</td>
                        <td class="px-5 py-3.5 font-bold text-polmind-blue">{{ $fee->code }}</td>
                        <td class="px-5 py-3.5 font-medium text-slate-800">
                            {{ $fee->name }}
                            @if($fee->is_installable)
                                <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-700">Bisa dicicil</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-polmind-blue">
                                {{ $typeLabels[$fee->type] ?? $fee->type }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 font-bold text-green-700">
                            Rp{{ number_format($fee->amount, 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-3.5">
                            @if($fee->is_active)
                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-700">Aktif</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex justify-end gap-2">
                                <button type="button"
                                        onclick="document.getElementById('edit-fee-component-{{ $fee->id }}').classList.toggle('hidden')"
                                        class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-polmind-blue">
                                    Edit
                                </button>

                                <form action="{{ route('admin.master-data.fee-components.toggle', $fee) }}"
                                    method="POST"
                                    onsubmit="return confirm('Ubah status aktif komponen biaya ini?')">
                                    @csrf
                                    @method('PATCH')

                                    <button type="submit"
                                            class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">
                                        {{ $fee->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <tr id="edit-fee-component-{{ $fee->id }}" class="hidden bg-slate-50">
                        <td colspan="8" class="px-6 py-5">
                            <form action="{{ route('admin.master-data.fee-components.update', $fee) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                                    <input type="text" name="code" value="{{ $fee->code }}" required
                                        class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <input type="text" name="name" value="{{ $fee->name }}" required
                                        class="xl:col-span-2 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <select name="type" required
                                            class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                                        <option value="registration" @selected($fee->type === 'registration')>Pendaftaran</option>
                                        <option value="re_registration" @selected($fee->type === 're_registration')>Daftar Ulang</option>
                                    </select>

                                    <input type="number" name="amount" value="{{ $fee->amount }}" min="0" required
                                        class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <input type="number" name="sort_order" value="{{ $fee->sort_order }}" min="0" required
                                        class="rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">

                                    <label class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">
                                        <input type="checkbox" name="is_active" value="1" @checked($fee->is_active)>
                                        Aktif
                                    </label>

                                    <label class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 xl:col-span-2"
                                           title="Khusus komponen daftar ulang, contoh UKT dan SPI">
                                        <input type="checkbox" name="is_installable" value="1" @checked($fee->is_installable)>
                                        Bisa dicicil (diaktifkan admin per camaba)
                                    </label>
                                </div>

                                <div class="mt-4 flex justify-end gap-3">
                                    <button type="button"
                                            onclick="document.getElementById('edit-fee-component-{{ $fee->id }}').classList.add('hidden')"
                                            class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">
                                        Batal
                                    </button>

                                    <button type="submit"
                                            class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">
                                        Update Biaya
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-sm font-medium text-slate-500">
                            Belum ada data komponen biaya.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Nominal daftar ulang per gelombang, per jalur, dan kategori prestasi --}}
@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100';
    $rupiah = fn ($amount) => 'Rp' . number_format((float) $amount, 0, ',', '.');
@endphp

@if($reRegistrationFees->isNotEmpty())
    <div class="mt-6 rounded-lg border border-blue-100 bg-blue-50 px-5 py-4 text-sm leading-6 text-slate-700">
        <p class="font-semibold text-polmind-blue">Urutan penentuan nominal invoice daftar ulang</p>
        <p class="mt-1">
            Kategori Prestasi (khusus SPI jalur Prestasi) → nominal Jalur → nominal Gelombang tempat camaba mendaftar → nominal default.
            Isi nominal rupiah final; kosongkan untuk memakai tingkat berikutnya. Perubahan berlaku untuk invoice yang dibuat atau dihitung ulang setelah disimpan.
        </p>
    </div>

    {{-- Per gelombang --}}
    <form action="{{ route('admin.master-data.wave-fees.update') }}" method="POST"
          class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm">
        @csrf
        @method('PUT')

        <div class="border-b border-slate-200 p-6">
            <h2 class="text-base font-semibold text-slate-900">Biaya Daftar Ulang per Gelombang</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Gelombang {{ $selectedPmbYear?->name ?? 'Tahun PMB terpilih' }}. Nominal mengikuti gelombang saat camaba mendaftar, meskipun camaba daftar ulang belakangan.
            </p>
        </div>

        @if($admissionWaves->isEmpty())
            <p class="p-6 text-sm text-slate-500">Belum ada gelombang pada Tahun PMB ini.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs text-slate-400 [&_th]:px-5 [&_th]:py-3.5 [&_th]:font-medium">
                        <tr>
                            <th>Gelombang</th>
                            @foreach($reRegistrationFees as $fee)
                                <th class="w-56">{{ $fee->name }} <span class="block font-normal">Default {{ $rupiah($fee->amount) }}</span></th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="table-zebra">
                        @foreach($admissionWaves as $wave)
                            <tr>
                                <td class="px-5 py-3 font-semibold text-slate-800">{{ $wave->name }}</td>
                                @foreach($reRegistrationFees as $fee)
                                    @php $amount = $fee->waveAmounts->firstWhere('admission_wave_id', $wave->id)?->amount; @endphp
                                    <td class="px-5 py-3">
                                        <input type="number" min="0" step="1000"
                                               name="amounts[{{ $wave->id }}][{{ $fee->id }}]"
                                               value="{{ old('amounts.' . $wave->id . '.' . $fee->id, $amount !== null ? (float) $amount : '') }}"
                                               placeholder="{{ (float) $fee->amount }}"
                                               aria-label="{{ $fee->name }} {{ $wave->name }}"
                                               class="{{ $input }}">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end border-t border-slate-200 p-6">
                <button type="submit" class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">Simpan Biaya per Gelombang</button>
            </div>
        @endif
    </form>

    {{-- Per jalur --}}
    @if($registrationPaths->isNotEmpty())
        <form action="{{ route('admin.master-data.path-fees.update') }}" method="POST"
              class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm">
            @csrf
            @method('PUT')

            <div class="border-b border-slate-200 p-6">
                <h2 class="text-base font-semibold text-slate-900">Biaya Daftar Ulang per Jalur</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Nominal jalur menggantikan nominal gelombang, contoh SPI khusus jalur Undangan.
                    Kosongkan SPI jalur Prestasi agar mengikuti Kategori Prestasi di bawah.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs text-slate-400 [&_th]:px-5 [&_th]:py-3.5 [&_th]:font-medium">
                        <tr>
                            <th>Jalur</th>
                            @foreach($reRegistrationFees as $fee)
                                <th class="w-56">{{ $fee->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="table-zebra">
                        @foreach($registrationPaths as $path)
                            <tr>
                                <td class="px-5 py-3 font-semibold text-slate-800">
                                    {{ $path->name }}
                                    @unless($path->is_active)
                                        <span class="text-xs font-normal text-slate-500">(nonaktif)</span>
                                    @endunless
                                </td>
                                @foreach($reRegistrationFees as $fee)
                                    @php $amount = $fee->pathAmounts->firstWhere('registration_path_id', $path->id)?->amount; @endphp
                                    <td class="px-5 py-3">
                                        <input type="number" min="0" step="1000"
                                               name="amounts[{{ $path->id }}][{{ $fee->id }}]"
                                               value="{{ old('amounts.' . $path->id . '.' . $fee->id, $amount !== null ? (float) $amount : '') }}"
                                               placeholder="Ikut gelombang"
                                               aria-label="{{ $fee->name }} jalur {{ $path->name }}"
                                               class="{{ $input }}">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end border-t border-slate-200 p-6">
                <button type="submit" class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">Simpan Biaya per Jalur</button>
            </div>
        </form>
    @endif
@endif

{{-- Kategori prestasi --}}
<div class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 md:flex-row md:items-center">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Kategori Prestasi (SPI Jalur Prestasi)</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Admin memilih kategori untuk camaba jalur Prestasi saat menyimpan keputusan seleksi. Nominal SPI kategori menggantikan SPI gelombang.
            </p>
        </div>

        <button type="button"
                onclick="document.getElementById('create-achievement-level-form').classList.toggle('hidden')"
                class="shrink-0 rounded-lg bg-polmind-yellow px-5 py-3 text-sm font-bold text-polmind-blue-dark shadow-sm transition hover:brightness-95">
            Tambah Kategori
        </button>
    </div>

    <form id="create-achievement-level-form"
          action="{{ route('admin.master-data.achievement-levels.store') }}"
          method="POST"
          class="{{ $errors->any() && old('_achievement_form') === 'create' ? '' : 'hidden' }} border-b border-slate-200 bg-slate-50 p-6">
        @csrf
        <input type="hidden" name="_achievement_form" value="create">
        @include('admin.master-data.sections.partials.achievement-level-fields', ['level' => null])
        <div class="mt-4 flex justify-end gap-3">
            <button type="button" onclick="document.getElementById('create-achievement-level-form').classList.add('hidden')"
                    class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">Batal</button>
            <button type="submit" class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">Simpan Kategori</button>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="border-b border-slate-200 text-xs text-slate-400 [&_th]:px-5 [&_th]:py-3.5 [&_th]:font-medium">
                <tr>
                    <th>Urutan</th>
                    <th>Kategori</th>
                    <th class="text-right">Nominal SPI</th>
                    <th class="text-right">Camaba</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="table-zebra">
                @forelse($achievementLevels as $level)
                    <tr>
                        <td class="px-5 py-3.5 text-slate-500">{{ $level->sort_order }}</td>
                        <td class="px-5 py-3.5">
                            <p class="font-semibold text-slate-900">{{ $level->name }}</p>
                            @if($level->description)
                                <p class="mt-0.5 text-xs text-slate-500">{{ $level->description }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right font-bold text-green-700">{{ $rupiah($level->spi_amount) }}</td>
                        <td class="px-5 py-3.5 text-right text-slate-700">{{ $level->applicants_count }}</td>
                        <td class="px-5 py-3.5">
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $level->is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $level->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex justify-end gap-2">
                                <button type="button"
                                        onclick="document.getElementById('edit-achievement-level-{{ $level->id }}').classList.toggle('hidden')"
                                        class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-polmind-blue">Edit</button>
                                <form action="{{ route('admin.master-data.achievement-levels.toggle', $level) }}" method="POST"
                                      onsubmit="return confirm('Ubah status kategori prestasi ini?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">
                                        {{ $level->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr id="edit-achievement-level-{{ $level->id }}" class="{{ $errors->any() && old('_achievement_form') === (string) $level->id ? '' : 'hidden' }} bg-slate-50">
                        <td colspan="6" class="px-6 py-5">
                            <form action="{{ route('admin.master-data.achievement-levels.update', $level) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="_achievement_form" value="{{ $level->id }}">
                                @include('admin.master-data.sections.partials.achievement-level-fields', ['level' => $level])
                                <div class="mt-4 flex justify-end gap-3">
                                    <button type="button" onclick="document.getElementById('edit-achievement-level-{{ $level->id }}').classList.add('hidden')"
                                            class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">Batal</button>
                                    <button type="submit" class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">Update Kategori</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-sm font-medium text-slate-500">
                            Belum ada kategori prestasi. Tanpa kategori, SPI camaba jalur Prestasi mengikuti nominal jalur atau gelombang.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
