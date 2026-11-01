{{-- Tombol ikon dengan keterangan saat kursor diarahkan. Variabel: $icon, $label, $url (null = nonaktif),
     $disabledLabel, $previewName (buka di modal pratinjau), $newTab --}}
<span class="group relative inline-flex">
    @if($url)
        <a href="{{ $url }}"
           @if($previewName ?? null) @click.prevent="$store.preview.show($el.href, @js($previewName))" @endif
           @if($newTab ?? false) target="_blank" rel="noopener" @endif
           aria-label="{{ $label }}"
           class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-polmind-blue hover:bg-blue-50 hover:text-polmind-blue focus-visible:outline-2 focus-visible:outline-polmind-blue">
            <x-icon :name="$icon" class="h-4.5 w-4.5" stroke-width="1.8" />
        </a>
    @else
        <span tabindex="0" aria-label="{{ $disabledLabel }}"
              class="flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg border border-slate-100 bg-slate-50 text-slate-300">
            <x-icon :name="$icon" class="h-4.5 w-4.5" stroke-width="1.8" />
        </span>
    @endif

    <span role="tooltip"
          class="pointer-events-none absolute bottom-full right-0 z-10 mb-2 whitespace-nowrap rounded-md bg-slate-900 px-2.5 py-1.5 text-xs font-semibold text-white opacity-0 shadow-lg transition group-focus-within:opacity-100 group-hover:opacity-100">
        {{ $url ? $label : $disabledLabel }}
    </span>
</span>
