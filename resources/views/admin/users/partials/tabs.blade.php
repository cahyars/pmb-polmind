{{-- Tab Kelola Akun: daftar akun dan Log Aktivitas --}}
<nav aria-label="Kelola Akun" class="flex flex-wrap gap-2">
    @foreach([
        ['label' => 'Akun', 'url' => route('admin.users.index'), 'active' => request()->routeIs('admin.users.index'), 'icon' => 'users'],
        ['label' => 'Log Aktivitas', 'url' => route('admin.activity-logs.index'), 'active' => request()->routeIs('admin.activity-logs.index'), 'icon' => 'clock'],
    ] as $tab)
        <a href="{{ $tab['url'] }}"
           @if($tab['active']) aria-current="page" @endif
           class="inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-semibold transition
           {{ $tab['active'] ? 'border-polmind-blue bg-polmind-blue text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-polmind-blue hover:text-polmind-blue' }}">
            <x-icon :name="$tab['icon']" class="h-4 w-4" stroke-width="1.8" />
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
