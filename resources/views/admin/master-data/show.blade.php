@extends('layouts.admin')

@php
    $meta = $sections[$section];
@endphp

@section('title', $meta['label'] . ' - Master Data')
@section('page_title', $meta['label'])
@section('page_subtitle', 'Master Data · ' . $meta['description'])

@section('content')
@php
    $statusClass = fn ($status) => match ($status) {
        'active', 'open' => 'bg-green-100 text-green-700',
        'closed', 'archived' => 'bg-slate-100 text-slate-600',
        'draft' => 'bg-yellow-100 text-yellow-700',
        default => 'bg-slate-100 text-slate-600',
    };

    $typeLabels = [
        'registration' => 'Pendaftaran',
        're_registration' => 'Daftar Ulang',
    ];
@endphp

<div class="space-y-6">
    {{-- Pindah cepat antar bagian Master Data --}}
    <nav aria-label="Bagian Master Data" class="flex flex-wrap gap-2">
        @foreach($sections as $key => $item)
            <a href="{{ route('admin.master-data.show', $key) }}"
               @if($key === $section) aria-current="page" @endif
               class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition
               {{ $key === $section ? 'border-polmind-blue bg-polmind-blue text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-polmind-blue hover:text-polmind-blue' }}">
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-5 py-4 text-sm font-semibold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    @include('admin.master-data.sections.' . $section)
</div>
@endsection
