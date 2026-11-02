@extends('layouts.admin')

@section('title', 'Detail Pengumuman')
@section('page_title', 'Detail Pengumuman')
@section('page_subtitle', 'Isi pengumuman dan status baca setiap camaba penerima.')

@section('content')
@php
    $unreadCount = $recipientCount - $readCount;
    $tabs = [
        null => ['Semua', $recipientCount],
        'sudah' => ['Sudah Dibaca', $readCount],
        'belum' => ['Belum Dibaca', $unreadCount],
    ];
@endphp

<div class="space-y-6">
    <a href="{{ route('admin.announcements.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-polmind-blue hover:underline">
        <x-icon name="arrow-left" class="h-4 w-4" stroke-width="2" /> Kembali ke Pengumuman
    </a>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="text-lg font-semibold text-slate-900">{{ $announcement->title }}</h2>
            <p class="mt-1 text-xs text-slate-500">
                {{ $announcement->created_at->translatedFormat('d F Y, H.i') }} WIB · oleh {{ $announcement->creator?->name ?? '-' }}
            </p>
            <div class="mt-5 whitespace-pre-line break-words text-sm leading-7 text-slate-700">{{ $announcement->body }}</div>
            @if($announcement->link_url)
                <a href="{{ $announcement->link_url }}" target="_blank" rel="noopener"
                   class="mt-5 inline-flex items-center gap-2 rounded-lg bg-polmind-blue px-4 py-2.5 text-sm font-semibold text-white">
                    {{ $announcement->link_label ?: 'Buka Tautan' }} <x-icon name="arrow-up-right" class="h-4 w-4" stroke-width="2" />
                </a>
            @endif
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Target</p>
                <p class="mt-2 text-sm font-semibold text-slate-800">{{ $announcement->audience_label }}</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500">Sudah dibaca</p>
                    <p class="mt-1 text-2xl font-bold text-green-700">{{ number_format($readCount, 0, ',', '.') }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500">Belum dibaca</p>
                    <p class="mt-1 text-2xl font-bold text-red-700">{{ number_format($unreadCount, 0, ',', '.') }}</p>
                </div>
            </div>
            <form action="{{ route('admin.announcements.destroy', $announcement) }}" method="POST"
                  onsubmit="return confirm(@js('Tarik pengumuman ' . $announcement->title . '? Pengumuman hilang dari portal semua penerima.'))">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700">Tarik Pengumuman</button>
            </form>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap gap-2 border-b border-slate-200 px-5 py-4">
            @foreach($tabs as $key => [$tabLabel, $tabCount])
                <a href="{{ route('admin.announcements.show', [$announcement, 'baca' => $key ?: null]) }}"
                   class="rounded-lg px-3 py-2 text-xs font-semibold {{ $readFilter === ($key ?: null) ? 'bg-polmind-blue text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                    {{ $tabLabel }} ({{ number_format($tabCount, 0, ',', '.') }})
                </a>
            @endforeach
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="border-b border-slate-200 text-xs text-slate-400 [&_th]:px-5 [&_th]:py-3.5 [&_th]:font-medium">
                    <tr>
                        <th>Camaba</th>
                        <th>Gelombang</th>
                        <th>Prodi</th>
                        <th>Dibaca</th>
                    </tr>
                </thead>
                <tbody class="table-zebra">
                    @forelse($recipients as $recipient)
                        <tr>
                            <td class="px-5 py-3.5">
                                <a href="{{ route('admin.applicants.show', $recipient->registration_number) }}" class="font-semibold text-slate-900 hover:text-polmind-blue hover:underline">{{ $recipient->full_name }}</a>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $recipient->registration_number }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-slate-600">{{ $recipient->admissionWave?->name ?? '-' }}</td>
                            <td class="px-5 py-3.5 text-slate-600">{{ $recipient->studyProgram?->name ?? '-' }}</td>
                            <td class="px-5 py-3.5">
                                @if($recipient->pivot->read_at)
                                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-700">{{ \Illuminate\Support\Carbon::parse($recipient->pivot->read_at)->translatedFormat('d/m/Y H.i') }}</span>
                                @else
                                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700">Belum dibaca</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-sm font-medium text-slate-500">Tidak ada camaba pada daftar ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $recipients->links() }}
</div>
@endsection
