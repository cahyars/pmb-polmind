@extends('layouts.camaba')

@section('title', 'Pengumuman')
@section('page_title', 'Pengumuman')
@section('page_subtitle', 'Informasi terbaru dari Admin PMB untuk Anda.')

@section('content')
<div class="mx-auto max-w-4xl space-y-4">
    @forelse($announcements as $announcement)
        @php($unread = ! $announcement->pivot->read_at)
        <a href="{{ route('camaba.announcements.show', $announcement->id) }}"
           class="block rounded-3xl border bg-white p-6 shadow-sm transition hover:border-polmind-blue {{ $unread ? 'border-blue-300 ring-1 ring-blue-100' : 'border-slate-200' }}">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $unread ? 'bg-polmind-blue text-white' : 'bg-slate-100 text-slate-500' }}">
                    <x-icon name="megaphone" class="h-5 w-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-base font-black text-slate-900">{{ $announcement->title }}</h2>
                        @if($unread)
                            <span class="rounded-full bg-red-600 px-2 py-0.5 text-[11px] font-bold text-white">Baru</span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs font-semibold text-slate-500">{{ $announcement->pivot->created_at->translatedFormat('d F Y, H:i') }} WIB</p>
                    <p class="mt-3 line-clamp-2 text-sm leading-6 text-slate-600">{{ $announcement->body }}</p>
                </div>
            </div>
        </a>
    @empty
        <div class="rounded-3xl border border-slate-200 bg-white p-10 text-center shadow-sm">
            <x-icon name="bell" class="mx-auto h-10 w-10 text-slate-300" />
            <p class="mt-4 text-sm font-semibold text-slate-500">Belum ada pengumuman. Pengumuman dari Admin PMB akan muncul di sini dan di ikon lonceng.</p>
        </div>
    @endforelse

    {{ $announcements->links() }}
</div>
@endsection
