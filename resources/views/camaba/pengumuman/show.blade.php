@extends('layouts.camaba')

@section('title', $announcement->title)
@section('page_title', 'Pengumuman')
@section('page_subtitle', 'Informasi dari Admin PMB.')

@section('content')
<div class="mx-auto max-w-3xl space-y-4">
    <a href="{{ route('camaba.announcements.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-polmind-blue hover:underline">
        <x-icon name="arrow-left" class="h-4 w-4" /> Semua Pengumuman
    </a>

    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-polmind-blue text-white">
                <x-icon name="megaphone" class="h-6 w-6" />
            </div>
            <div class="min-w-0">
                <h2 class="text-xl font-black text-polmind-blue">{{ $announcement->title }}</h2>
                <p class="mt-1 text-xs font-semibold text-slate-500">{{ $announcement->pivot->created_at->translatedFormat('l, d F Y, H:i') }} WIB</p>
            </div>
        </div>

        <div class="mt-6 whitespace-pre-line break-words text-sm leading-7 text-slate-700">{{ $announcement->body }}</div>

        @if($announcement->link_url)
            <a href="{{ $announcement->link_url }}" target="_blank" rel="noopener"
               class="mt-6 inline-flex items-center gap-2 rounded-xl bg-polmind-blue px-5 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                {{ $announcement->link_label ?: 'Buka Tautan' }} <x-icon name="arrow-up-right" class="h-4 w-4" stroke-width="2" />
            </a>
        @endif
    </article>
</div>
@endsection
