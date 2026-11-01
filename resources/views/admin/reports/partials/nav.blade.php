{{-- Tab submenu Laporan. Variabel: $sections, $section, $filters --}}
<nav aria-label="Jenis laporan" class="flex flex-wrap gap-2">
    @foreach($sections as $key => $item)
        <a href="{{ route('admin.reports.show', ['section' => $key] + $filters) }}"
           @if($key === $section) aria-current="page" @endif
           class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition
           {{ $key === $section ? 'border-polmind-blue bg-polmind-blue text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-polmind-blue hover:text-polmind-blue' }}">
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
