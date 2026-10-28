<div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <p class="text-sm font-semibold text-slate-500">{{ $label }}</p>
    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $value }}</p>
    @isset($desc)
        <p class="mt-1 text-xs font-medium text-slate-500">{{ $desc }}</p>
    @endisset
</div>
