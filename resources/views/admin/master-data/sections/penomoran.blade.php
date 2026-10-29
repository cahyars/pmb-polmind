{{-- Penomoran NIM --}}
@php
    $missingNimCode = $studyPrograms->where('is_active', true)->filter(fn ($program) => blank($program->nim_code));
@endphp

<div class="grid gap-6 xl:grid-cols-3">
    <form action="{{ route('admin.master-data.numbering.update') }}"
          method="POST"
          x-data="{
              format: @js(old('nim_format', $nimFormat)),
              digits: @js((int) old('nim_digits', $nimDigits)),
              year: @js((string) ($selectedPmbYear?->year ?? now()->year)),
              preview(prodi, urut) {
                  return this.format
                      .replaceAll('{PRODI}', prodi)
                      .replaceAll('{KELAS}', 'A')
                      .replaceAll('{TAHUN2}', this.year.slice(-2))
                      .replaceAll('{TAHUN}', this.year)
                      .replaceAll('{URUT}', String(urut).padStart(this.digits, '0'));
              },
          }"
          class="rounded-xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
        @csrf
        @method('PUT')

        <div class="border-b border-slate-200 p-6">
            <h2 class="text-base font-semibold text-slate-900">Format NIM</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                NIM dibubuhkan dari menu Daftar Ulang untuk camaba yang sudah membayar daftar ulang (UKT, SPI, atau keduanya).
                Nomor urut mengikuti tanggal daftar ulang pertama sampai terakhir.
            </p>
        </div>

        <div class="grid gap-5 p-6 md:grid-cols-2">
            <div class="md:col-span-2">
                <label for="nim_format" class="text-sm font-semibold text-slate-700">Pola NIM</label>
                <input type="text" id="nim_format" name="nim_format" value="{{ old('nim_format', $nimFormat) }}" x-model="format" required maxlength="50"
                       class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 font-mono text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach(\App\Services\NimGenerator::TOKENS as $token => $label)
                        <button type="button"
                                @click="format += @js($token)"
                                title="{{ $label }}"
                                class="rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 font-mono text-xs font-semibold text-slate-700 hover:border-polmind-blue hover:text-polmind-blue">
                            {{ $token }}
                        </button>
                    @endforeach
                </div>
                <ul class="mt-3 grid gap-1 text-xs leading-5 text-slate-500 sm:grid-cols-2">
                    @foreach(\App\Services\NimGenerator::TOKENS as $token => $label)
                        <li><span class="font-mono font-semibold text-slate-700">{{ $token }}</span> {{ $label }}</li>
                    @endforeach
                </ul>
            </div>

            <div>
                <label for="nim_digits" class="text-sm font-semibold text-slate-700">Panjang Nomor Urut</label>
                <select id="nim_digits" name="nim_digits" x-model.number="digits"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    @foreach([3, 4, 5, 6] as $digit)
                        <option value="{{ $digit }}" @selected((int) old('nim_digits', $nimDigits) === $digit)>{{ $digit }} digit ({{ str_pad('1', $digit, '0', STR_PAD_LEFT) }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="nim_scope" class="text-sm font-semibold text-slate-700">Nomor Urut Dihitung</label>
                <select id="nim_scope" name="nim_scope"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                    @foreach(\App\Services\NimGenerator::SCOPES as $key => $label)
                        <option value="{{ $key }}" @selected(old('nim_scope', $nimScope) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 md:col-span-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-polmind-blue">Contoh hasil</p>
                <p class="mt-2 font-mono text-lg font-bold text-slate-900">
                    <span x-text="preview('A', 1)"></span>,
                    <span x-text="preview('A', 2)"></span>,
                    <span x-text="preview('B', 1)"></span>
                </p>
                <p class="mt-1 text-xs text-slate-500">Tahun akademik mengikuti {{ $selectedPmbYear?->name ?? 'Tahun PMB terpilih' }}.</p>
            </div>
        </div>

        <div class="flex justify-end border-t border-slate-200 p-6">
            <button type="submit" class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">
                Simpan Format NIM
            </button>
        </div>
    </form>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-6">
            <h2 class="text-base font-semibold text-slate-900">Kode Prodi pada NIM</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Diatur di
                <a href="{{ route('admin.master-data.show', 'prodi') }}" class="font-semibold text-polmind-blue hover:underline">Master Data Program Studi</a>.
            </p>
        </div>

        <ul class="divide-y divide-slate-100">
            @forelse($studyPrograms as $program)
                <li class="flex items-center justify-between gap-3 px-6 py-3.5 text-sm">
                    <span class="text-slate-700">
                        <span class="font-semibold text-slate-900">{{ $program->code }}</span>
                        <span class="text-slate-500">· {{ $program->name }}</span>
                    </span>
                    @if($program->nim_code)
                        <span class="rounded-md bg-blue-50 px-2.5 py-1 font-mono text-sm font-bold text-polmind-blue">{{ $program->nim_code }}</span>
                    @else
                        <span class="rounded-full bg-yellow-100 px-3 py-1 text-xs font-bold text-yellow-700">Belum diisi</span>
                    @endif
                </li>
            @empty
                <li class="px-6 py-6 text-sm text-slate-500">Belum ada data prodi.</li>
            @endforelse
        </ul>

        @if($missingNimCode->isNotEmpty())
            <p class="border-t border-slate-200 px-6 py-4 text-xs leading-5 text-yellow-700">
                Camaba dari prodi tanpa kode NIM akan dilewati saat Generate NIM.
            </p>
        @endif
    </div>
</div>

{{-- Nomor Kwitansi --}}
<form action="{{ route('admin.master-data.numbering.receipt') }}"
      method="POST"
      enctype="multipart/form-data"
      x-data="{
          format: @js(old('receipt_format', $receiptFormat)),
          digits: @js((int) old('receipt_digits', $receiptDigits)),
          year: @js((string) ($selectedPmbYear?->year ?? now()->year)),
          preview(jenis, urut) {
              const roman = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
              const month = new Date().getMonth();
              return this.format
                  .replaceAll('{JENIS}', jenis)
                  .replaceAll('{TAHUN}', this.year)
                  .replaceAll('{BULAN}', String(month + 1).padStart(2, '0'))
                  .replaceAll('{ROMAWI}', roman[month])
                  .replaceAll('{URUT}', String(urut).padStart(this.digits, '0'));
          },
      }"
      class="rounded-xl border border-slate-200 bg-white shadow-sm">
    @csrf
    @method('PUT')

    <div class="border-b border-slate-200 p-6">
        <h2 class="text-base font-semibold text-slate-900">Kwitansi Pembayaran</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Nomor kwitansi diberikan saat pembayaran divalidasi admin dan dapat dicetak camaba dari halaman Pembayaran.
            Nomor urut berjalan per Tahun PMB. Kwitansi yang sudah bernomor tidak berubah saat format diganti.
        </p>
    </div>

    <div class="grid gap-5 p-6 md:grid-cols-3">
        <div class="md:col-span-2">
            <label for="receipt_format" class="text-sm font-semibold text-slate-700">Pola Nomor Kwitansi</label>
            <input type="text" id="receipt_format" name="receipt_format" value="{{ old('receipt_format', $receiptFormat) }}" x-model="format" required maxlength="50"
                   class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 font-mono text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach(\App\Services\ReceiptNumber::TOKENS as $token => $label)
                    <button type="button"
                            @click="format += @js($token)"
                            title="{{ $label }}"
                            class="rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 font-mono text-xs font-semibold text-slate-700 hover:border-polmind-blue hover:text-polmind-blue">
                        {{ $token }}
                    </button>
                @endforeach
            </div>
            <ul class="mt-3 grid gap-1 text-xs leading-5 text-slate-500 sm:grid-cols-2">
                @foreach(\App\Services\ReceiptNumber::TOKENS as $token => $label)
                    <li><span class="font-mono font-semibold text-slate-700">{{ $token }}</span> {{ $label }}</li>
                @endforeach
            </ul>
        </div>

        <div>
            <label for="receipt_digits" class="text-sm font-semibold text-slate-700">Panjang Nomor Urut</label>
            <select id="receipt_digits" name="receipt_digits" x-model.number="digits"
                    class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                @foreach([3, 4, 5, 6, 7, 8] as $digit)
                    <option value="{{ $digit }}" @selected((int) old('receipt_digits', $receiptDigits) === $digit)>{{ $digit }} digit ({{ str_pad('1', $digit, '0', STR_PAD_LEFT) }})</option>
                @endforeach
            </select>
        </div>

        <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 md:col-span-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-polmind-blue">Contoh hasil</p>
            <p class="mt-2 break-all font-mono text-lg font-bold text-slate-900">
                <span x-text="preview('PMB', 1)"></span>,
                <span x-text="preview('DU', 2)"></span>
            </p>
        </div>

        <div class="border-t border-slate-200 pt-5 md:col-span-3">
            <h3 class="text-sm font-semibold text-slate-900">Penandatangan Kwitansi</h3>
            <p class="mt-1 text-xs leading-5 text-slate-500">Tampil di bagian tanda tangan kwitansi. Kosongkan nama dan gambar tanda tangan jika kwitansi ditandatangani manual.</p>
        </div>

        <div>
            <label for="receipt_signer_title" class="text-sm font-semibold text-slate-700">Jabatan</label>
            <input type="text" id="receipt_signer_title" name="receipt_signer_title" required maxlength="100"
                   value="{{ old('receipt_signer_title', $receiptSigner['title']) }}" placeholder="Contoh: Bendahara PMB"
                   class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
        </div>

        <div class="md:col-span-2">
            <label for="receipt_signer_name" class="text-sm font-semibold text-slate-700">Nama</label>
            <input type="text" id="receipt_signer_name" name="receipt_signer_name" maxlength="100"
                   value="{{ old('receipt_signer_name', $receiptSigner['name']) }}" placeholder="Contoh: Siti Rahmawati, S.E."
                   class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
        </div>

        @foreach([
            'signature' => ['Gambar Tanda Tangan', 'PNG latar transparan, hasil scan tanda tangan admin keuangan.'],
            'stamp' => ['Gambar Stempel', 'PNG latar transparan. Dicetak di samping tanda tangan.'],
        ] as $image => [$imageLabel, $imageHint])
            <div class="rounded-lg border border-slate-200 p-4 md:col-span-3 xl:col-span-1" x-data="{ remove: false }">
                <label for="receipt_{{ $image }}" class="text-sm font-semibold text-slate-700">{{ $imageLabel }} <span class="font-normal text-slate-400">(opsional)</span></label>

                @if($receiptSigner[$image])
                    <div class="mt-2 flex items-center gap-4" :class="remove && 'opacity-40'">
                        <div class="flex h-20 w-40 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-[repeating-conic-gradient(#f1f5f9_0_25%,white_0_50%)] bg-[length:16px_16px]">
                            <img src="{{ $receiptSigner[$image] }}" alt="{{ $imageLabel }} saat ini" class="max-h-18 max-w-36 object-contain">
                        </div>
                        <label class="flex items-center gap-2 text-xs font-semibold text-red-700">
                            <input type="checkbox" name="remove_receipt_{{ $image }}" value="1" x-model="remove"> Hapus gambar
                        </label>
                    </div>
                @endif

                <input type="file" id="receipt_{{ $image }}" name="receipt_{{ $image }}" accept="image/png,image/jpeg"
                       class="mt-2 w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-polmind-blue">
                <p class="mt-1 text-xs text-slate-500">{{ $imageHint }} Maks. 1 MB.{{ $receiptSigner[$image] ? ' Pilih file baru untuk mengganti.' : '' }}</p>
            </div>
        @endforeach
    </div>

    <div class="flex justify-end border-t border-slate-200 p-6">
        <button type="submit" class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">
            Simpan Pengaturan Kwitansi
        </button>
    </div>
</form>
