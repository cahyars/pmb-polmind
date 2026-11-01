{{-- Pagination daftar admin + pilihan jumlah data per halaman (?per_page=, diingat per halaman oleh Controller::perPage). --}}
@props(['paginator'])

@php
    $options = \App\Http\Controllers\Controller::PER_PAGE_OPTIONS;
@endphp

@if($paginator->total() > min($options))
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
        <label class="flex shrink-0 items-center gap-2 text-sm text-slate-600">
            Tampilkan
            <select aria-label="Jumlah data per halaman"
                    onchange="const url = new URL(window.location.href); url.searchParams.set('per_page', this.value); url.searchParams.delete('page'); window.location = url;"
                    class="rounded-lg border border-slate-300 bg-white py-2 pl-3 pr-8 text-sm font-semibold text-slate-700 outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100">
                @foreach($options as $option)
                    <option value="{{ $option }}" @selected($paginator->perPage() === $option)>{{ $option }}</option>
                @endforeach
            </select>
            per halaman
        </label>

        <div class="min-w-0 flex-1">
            {{ $paginator->links() }}
        </div>
    </div>
@endif
