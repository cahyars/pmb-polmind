@extends('layouts.admin')

@section('title', 'Token API SIAKAD')
@section('page_title', 'Token API SIAKAD')
@section('page_subtitle', 'Token yang dipakai SIAKAD untuk menarik data mahasiswa baru dari PMB.')

@section('content')
@php
    $input = 'w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100';
    $generatedToken = session('generated_token');
    $snippets = $generatedToken ? [
        ['label' => 'Token', 'value' => $generatedToken],
        ['label' => 'Konfigurasi .env SIAKAD', 'value' => "PMB_API_URL={$baseUrl}\nPMB_API_TOKEN={$generatedToken}"],
        ['label' => 'Tes koneksi (curl)', 'value' => "curl -H \"Authorization: Bearer {$generatedToken}\" -H \"Accept: application/json\" \"{$baseUrl}/mahasiswa\""],
    ] : [];
@endphp

<div class="space-y-8">

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-5 py-4 text-sm font-semibold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    @if($generatedToken)
        <div class="rounded-xl border-2 border-amber-300 bg-amber-50 p-6 shadow-sm">
            <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900">
                <x-icon name="key" class="h-5 w-5 text-amber-600" stroke-width="2" />
                Salin token sekarang
            </h2>
            <p class="mt-2 text-sm leading-6 text-amber-900">
                Token hanya ditampilkan sekali. Setelah halaman ini ditutup atau dimuat ulang, token tidak bisa dilihat lagi
                dan harus dibuat ulang. Kirim ke tim SIAKAD lewat jalur pribadi.
            </p>

            <div class="mt-5 space-y-4">
                @foreach($snippets as $snippet)
                    <div x-data="{ copied: false }">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $snippet['label'] }}</p>
                            <button type="button"
                                    @click="navigator.clipboard.writeText($refs.value.value).then(() => { copied = true; setTimeout(() => copied = false, 2000) }, () => { $refs.value.select() })"
                                    class="rounded-lg bg-polmind-blue px-3 py-1.5 text-xs font-semibold text-white transition hover:brightness-110">
                                <span x-text="copied ? 'Tersalin' : 'Salin'">Salin</span>
                            </button>
                        </div>
                        <textarea x-ref="value" readonly rows="{{ substr_count($snippet['value'], "\n") + 1 }}" @focus="$el.select()"
                                  class="mt-2 w-full resize-none rounded-lg border border-amber-200 bg-white px-4 py-3 font-mono text-xs text-slate-800 outline-none">{{ $snippet['value'] }}</textarea>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_380px]">
        <div class="space-y-8">
            {{-- Status token --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col justify-between gap-4 md:flex-row md:items-start">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Status API</h2>
                        @if($enabled)
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Token berakhiran <span class="font-mono font-bold text-slate-800">…{{ $token['hint'] }}</span>,
                                dibuat {{ \Illuminate\Support\Carbon::parse($token['created_at'])->translatedFormat('d M Y H:i') }}
                                oleh {{ $token['created_by'] ?? '-' }}.
                            </p>
                        @else
                            <p class="mt-2 text-sm leading-6 text-slate-600">Belum ada token. Selama token belum dibuat, SIAKAD tidak bisa menarik data.</p>
                        @endif
                    </div>

                    @if($enabled)
                        <span class="shrink-0 rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-700">Aktif</span>
                    @else
                        <span class="shrink-0 rounded-full bg-slate-200 px-3 py-1 text-xs font-bold text-slate-600">Nonaktif</span>
                    @endif
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <form action="{{ route('admin.integrations.api.generate') }}" method="POST"
                          @if($enabled) onsubmit="return confirm('Buat token baru? Token lama langsung tidak berlaku dan SIAKAD harus memakai token baru.')" @endif>
                        @csrf
                        <button type="submit" class="rounded-lg bg-polmind-yellow px-5 py-3 text-sm font-bold text-polmind-blue-dark shadow-sm transition hover:brightness-95">
                            {{ $enabled ? 'Generate Token Baru' : 'Generate Token' }}
                        </button>
                    </form>

                    @if($enabled)
                        <form action="{{ route('admin.integrations.api.revoke') }}" method="POST"
                              onsubmit="return confirm('Cabut token? SIAKAD tidak bisa menarik data sampai token baru dibuat.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg border border-red-200 bg-white px-5 py-3 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                                Cabut Token
                            </button>
                        </form>
                    @endif
                </div>

                <p class="mt-4 text-xs leading-5 text-slate-500">
                    PMB hanya menyimpan sidik token (hash), bukan token aslinya. Bila token hilang atau bocor, buat token baru.
                </p>
            </div>

            {{-- Pembatasan IP --}}
            <form action="{{ route('admin.integrations.api.ips') }}" method="POST" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                @method('PUT')

                <h2 class="text-base font-semibold text-slate-900">Pembatasan IP</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Opsional. Isi IP publik server SIAKAD agar token hanya bisa dipakai dari server itu. Kosongkan untuk menerima dari IP mana pun.
                </p>

                <textarea name="allowed_ips" rows="3" placeholder="Contoh: 103.10.20.30, 103.10.20.31"
                          class="mt-4 font-mono {{ $input }}">{{ old('allowed_ips', implode(', ', $allowedIps)) }}</textarea>

                <div class="mt-4 flex justify-end">
                    <button type="submit" class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">Simpan IP</button>
                </div>
            </form>
        </div>

        {{-- Endpoint --}}
        <aside class="rounded-xl border border-blue-200 bg-blue-50 p-6">
            <h2 class="text-base font-semibold text-slate-900">Endpoint untuk SIAKAD</h2>
            <p class="mt-2 text-sm leading-6 text-slate-700">
                Setiap request membawa header <code class="rounded bg-white px-1 text-xs">Authorization: Bearer &lt;token&gt;</code>.
            </p>

            <ul class="mt-4 space-y-2 text-xs">
                @foreach([
                    ['GET', '/mahasiswa', 'Daftar siap ditarik'],
                    ['GET', '/mahasiswa/{id_pendaftar}', 'Detail satu mahasiswa'],
                    ['POST', '/mahasiswa/{id_pendaftar}/konfirmasi', 'Hasil: berhasil / gagal'],
                ] as [$method, $path, $label])
                    <li class="rounded-lg bg-white p-3">
                        <p class="font-semibold text-slate-700">{{ $label }}</p>
                        <p class="mt-1 break-all font-mono text-slate-500"><span class="font-bold text-polmind-blue">{{ $method }}</span> {{ $baseUrl . $path }}</p>
                    </li>
                @endforeach
            </ul>

            <a href="{{ route('admin.integrations.index') }}" class="mt-5 inline-flex text-sm font-semibold text-polmind-blue hover:underline">
                Lihat monitoring sinkron →
            </a>
        </aside>
    </div>
</div>
@endsection
