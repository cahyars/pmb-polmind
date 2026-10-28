{{-- Tombol unduh brosur: satu brosur langsung diunduh, lebih dari satu menampilkan pilihan.
     $dropUp membuka pilihan ke atas, dipakai di hero yang memotong konten di bawahnya. --}}
@php
    $downloadIcon = '<svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.69L6.03 8.22a.75.75 0 0 0-1.06 1.06l4.5 4.5a.75.75 0 0 0 1.06 0l4.5-4.5a.75.75 0 1 0-1.06-1.06l-3.22 3.22V2.75Z"/><path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/></svg>';
@endphp

@if($brochures->count() === 1)
    <a href="{{ route('brochures.download', $brochures->first()) }}" class="{{ $buttonClass }}">
        {!! $downloadIcon !!}
        Unduh Brosur
    </a>
@else
    <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false" class="relative {{ $wrapperClass ?? '' }}">
        <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-haspopup="true" class="{{ $buttonClass }} w-full">
            {!! $downloadIcon !!}
            Unduh Brosur
            <x-icon name="chevron" class="h-4 w-4 shrink-0 transition" ::class="open && 'rotate-180'" stroke-width="2" />
        </button>

        <div x-show="open" x-cloak x-transition.opacity
             class="absolute left-0 z-30 {{ ($dropUp ?? false) ? 'bottom-full mb-2' : 'mt-2' }} w-full min-w-64 overflow-hidden rounded-xl bg-white py-1 text-left shadow-xl ring-1 ring-slate-200">
            @foreach($brochures as $brochure)
                <a href="{{ route('brochures.download', $brochure) }}" class="block px-4 py-3 hover:bg-blue-50">
                    <span class="block text-sm font-bold text-slate-900">{{ $brochure->title }}</span>
                    <span class="mt-0.5 block text-xs text-slate-500">{{ $brochure->extension }} · {{ $brochure->size_label }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endif
