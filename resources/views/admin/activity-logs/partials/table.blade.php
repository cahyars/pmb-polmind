{{-- Tabel Log Aktivitas. Variabel: $logs, $showSubject (kolom Data; disembunyikan di Riwayat Detail Camaba) --}}
@php
    $showSubject ??= true;
    $actorBadge = [
        'user' => 'bg-blue-100 text-polmind-blue',
        'applicant' => 'bg-teal-100 text-teal-800',
        'guest' => 'bg-red-100 text-red-700',
        'system' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<div class="overflow-x-auto">
    <table class="w-full min-w-[860px] text-left text-sm">
        <thead class="border-b border-slate-200 text-xs text-slate-400 [&_th]:px-4 [&_th]:py-3 [&_th]:font-medium">
            <tr>
                <th class="w-40">Waktu</th>
                <th>Pelaku</th>
                <th>Aksi</th>
                @if($showSubject)<th>Data</th>@endif
                <th>Perubahan</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($logs as $log)
                @php($changes = $log->changes ?? [])
                <tr class="align-top" x-data="{ open: false }">
                    <td class="px-4 py-3 text-xs text-slate-500">
                        <span class="block font-semibold tabular-nums text-slate-700">{{ $log->created_at->translatedFormat('d/m/Y') }}</span>
                        <span class="tabular-nums">{{ $log->created_at->format('H:i:s') }} WIB</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="block font-semibold text-slate-800">{{ $log->actor_name }}</span>
                        <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[11px] font-bold {{ $actorBadge[$log->actor_type] ?? $actorBadge['system'] }}">
                            {{ $log->actor_role ?? \App\Models\ActivityLog::ACTOR_TYPES[$log->actor_type] ?? $log->actor_type }}
                        </span>
                        @if($log->impersonator_name)
                            <span class="mt-1 block text-[11px] font-semibold text-amber-700">via Login As oleh {{ $log->impersonator_name }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="font-medium {{ $log->actor_type === 'guest' ? 'text-red-700' : 'text-slate-800' }}">{{ $log->description }}</span>
                        @if($log->ip_address)
                            <span class="mt-1 block text-[11px] text-slate-400" title="{{ $log->user_agent }}">IP {{ $log->ip_address }}</span>
                        @endif
                    </td>
                    @if($showSubject)
                        <td class="px-4 py-3 text-slate-700">
                            @if($log->applicant)
                                <a href="{{ route('admin.applicants.show', $log->applicant->registration_number) }}#riwayat"
                                   class="font-medium text-polmind-blue hover:underline">{{ $log->subject_label }}</a>
                            @else
                                {{ $log->subject_label ?? '-' }}
                            @endif
                        </td>
                    @endif
                    <td class="px-4 py-3">
                        @if($changes)
                            <button type="button" @click="open = ! open"
                                    class="text-xs font-semibold text-polmind-blue hover:underline"
                                    x-text="open ? 'Tutup rincian' : @js(count($changes) . ' kolom · lihat rincian')">{{ count($changes) }} kolom · lihat rincian</button>

                            <p x-show="! open" class="mt-1 max-w-xs truncate text-xs text-slate-500">
                                {{ collect($changes)->pluck('label')->take(3)->implode(', ') }}{{ count($changes) > 3 ? ', ...' : '' }}
                            </p>

                            <dl x-show="open" x-cloak class="mt-2 min-w-72 space-y-1.5 rounded-lg bg-slate-50 p-3 text-xs">
                                @foreach($changes as $change)
                                    <div class="grid grid-cols-[110px_1fr] gap-2">
                                        <dt class="font-semibold text-slate-500">{{ $change['label'] }}</dt>
                                        <dd class="min-w-0 break-words text-slate-800">
                                            @if($change['old'] !== null)
                                                <span class="text-red-700 line-through decoration-red-300">{{ $change['old'] }}</span>
                                                <span class="text-slate-400">→</span>
                                            @endif
                                            <span class="{{ $change['old'] !== null ? 'font-semibold text-green-800' : '' }}">{{ $change['new'] ?? '(kosong)' }}</span>
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        @else
                            <span class="text-xs text-slate-400">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $showSubject ? 5 : 4 }}" class="px-4 py-10 text-center text-sm font-medium text-slate-500">
                        Belum ada aktivitas tercatat.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
