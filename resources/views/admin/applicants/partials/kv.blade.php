{{-- Tabel label–nilai rapat ala SIAKAD. Variabel: $rows (label => nilai; kosong tampil "-") --}}
<dl>
    @foreach($rows as $label => $value)
        <div class="grid grid-cols-[150px_1fr] gap-3 border-b border-slate-100 px-4 py-2.5 text-sm">
            <dt class="pt-0.5 text-xs font-semibold text-polmind-blue/70">{{ $label }}</dt>
            <dd class="min-w-0 break-words text-slate-800">{{ filled($value) ? $value : '-' }}</dd>
        </div>
    @endforeach
</dl>
