{{-- Pilihan prodi bernomor; pilihan pertama ditandai hijau jika camaba lulus seleksi. --}}
@if($row['programs'])
    <ol class="space-y-2">
        @foreach($row['programs'] as $index => $program)
            <li class="flex gap-2 text-sm leading-5 text-slate-700">
                <span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full text-[10px] font-bold text-white {{ $program['accepted'] ? 'bg-green-600' : 'bg-slate-600' }}">
                    {{ $index + 1 }}
                </span>
                <span class="min-w-0">{{ $program['label'] }}</span>
            </li>
        @endforeach
    </ol>
@else
    <p class="text-sm text-slate-400">Belum memilih prodi</p>
@endif
