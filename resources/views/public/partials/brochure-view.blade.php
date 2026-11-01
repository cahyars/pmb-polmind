{{-- Tombol lihat brosur: satu brosur langsung membuka preview, lebih dari satu menampilkan pilihan.
     Unduhan tersedia di halaman preview. $dropUp membuka pilihan ke atas, dipakai di hero yang memotong konten di bawahnya. --}}
@if($brochures->count() === 1)
    <a href="{{ route('brochures.show', $brochures->first()) }}" class="{{ $buttonClass }}">
        <x-icon name="eye" class="h-4 w-4 shrink-0" stroke-width="2" />
        Lihat Brosur
    </a>
@else
    <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false" class="relative {{ $wrapperClass ?? '' }}">
        <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-haspopup="true" class="{{ $buttonClass }} w-full">
            <x-icon name="eye" class="h-4 w-4 shrink-0" stroke-width="2" />
            Lihat Brosur
            <x-icon name="chevron" class="h-4 w-4 shrink-0 transition" ::class="open && 'rotate-180'" stroke-width="2" />
        </button>

        <div x-show="open" x-cloak x-transition.opacity
             class="absolute left-0 z-30 {{ ($dropUp ?? false) ? 'bottom-full mb-2' : 'mt-2' }} w-full min-w-64 overflow-hidden rounded-xl bg-white py-1 text-left shadow-xl ring-1 ring-slate-200">
            @foreach($brochures as $brochure)
                <a href="{{ route('brochures.show', $brochure) }}" class="block px-4 py-3 hover:bg-blue-50">
                    <span class="block text-sm font-bold text-slate-900">{{ $brochure->title }}</span>
                    <span class="mt-0.5 block text-xs text-slate-500">{{ $brochure->extension }} · {{ $brochure->size_label }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endif
