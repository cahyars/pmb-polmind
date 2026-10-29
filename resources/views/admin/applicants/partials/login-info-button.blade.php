{{-- Tombol kirim Informasi Pendaftar (ID Pendaftar + PIN) ke WhatsApp camaba. Variabel: $large --}}
@if($loginPin && $applicant->phone)
    <div x-data="{ preview: false, copied: false }" class="{{ $large ? 'mt-4' : '' }}">
        <form action="{{ route('admin.applicants.login-info', $applicant->registration_number) }}" method="POST" target="_blank">
            @csrf
            <button type="submit"
                    class="flex w-full items-center gap-3 rounded-lg bg-green-600 px-4 py-3 text-left text-white transition hover:bg-green-700">
                <x-icon name="chat" class="h-5 w-5 shrink-0" stroke-width="1.8" />
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-bold">Kirim Info Login ke WhatsApp</span>
                    <span class="block text-xs opacity-90">ID Pendaftar dan PIN ke {{ $applicant->phone }}</span>
                </span>
                <x-icon name="arrow-up-right" class="h-4 w-4 shrink-0 opacity-80" stroke-width="2" />
            </button>
        </form>

        <div class="mt-2 flex gap-3 text-xs font-semibold">
            <button type="button" @click="preview = ! preview" class="text-polmind-blue hover:underline" x-text="preview ? 'Tutup pratinjau' : 'Lihat isi pesan'">Lihat isi pesan</button>
            <button type="button" @click="navigator.clipboard.writeText(@js($loginInfoText)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                    class="text-polmind-blue hover:underline" x-text="copied ? 'Tersalin' : 'Salin pesan'">Salin pesan</button>
        </div>

        <pre x-show="preview" x-cloak class="mt-2 whitespace-pre-wrap rounded-lg border border-slate-200 bg-slate-50 p-3 font-sans text-xs leading-5 text-slate-700">{{ $loginInfoText }}</pre>
    </div>
@elseif($loginPin)
    <p class="rounded-lg bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-500 {{ $large ? 'mt-4' : '' }}">
        Isi nomor HP camaba untuk mengirim info login lewat WhatsApp.
    </p>
@elseif(! $large)
    {{-- Camaba lupa password: reset ke PIN tanggal lahir, lalu tombol kirim info login muncul. --}}
    @if($applicant->birth_date)
        <form action="{{ route('admin.applicants.reset-password', $applicant->registration_number) }}" method="POST"
              x-data
              @submit.prevent="window.Swal.fire({
                  icon: 'warning',
                  title: 'Reset PIN camaba?',
                  html: @js('Password <b>' . e($applicant->full_name) . '</b> akan diganti menjadi tanggal lahirnya (<b>' . $applicant->birth_date->format('dmY') . '</b>). Pastikan yang meminta benar-benar camaba ini.'),
                  showCancelButton: true,
                  confirmButtonText: 'Ya, reset PIN',
                  cancelButtonText: 'Batal',
                  confirmButtonColor: '#003B82',
              }).then((result) => result.isConfirmed && $el.submit())">
            @csrf
            @method('PATCH')
            <button type="submit"
                    class="flex w-full items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-left text-amber-900 transition hover:bg-amber-100">
                <x-icon name="key" class="h-5 w-5 shrink-0 text-amber-600" stroke-width="1.8" />
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-bold">Reset PIN ke Tanggal Lahir</span>
                    <span class="block text-xs opacity-80">Untuk camaba yang lupa password</span>
                </span>
            </button>
        </form>
    @else
        <p class="rounded-lg bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-500">
            Reset PIN butuh tanggal lahir camaba. Tanggal lahir belum diisi di biodata.
        </p>
    @endif
@endif
