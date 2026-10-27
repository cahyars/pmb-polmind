<div class="flex flex-wrap gap-1">
    @foreach($row['badges'] as [$label, $class])
        <span class="rounded px-1.5 py-0.5 text-[11px] font-semibold leading-4 {{ $class }}">{{ $label }}</span>
    @endforeach
</div>
