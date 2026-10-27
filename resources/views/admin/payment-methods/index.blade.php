@extends('layouts.admin')

@section('title', 'Tujuan Pembayaran')
@section('page_title', 'Tujuan Pembayaran')
@section('page_subtitle', 'Atur rekening kampus dan alternatif pembayaran yang ditampilkan ke camaba.')

@section('content')
@php
    $input = 'mt-2 w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100';
    $label = 'text-sm font-semibold text-slate-700';
    $optional = '<span class="font-semibold text-slate-400">(opsional)</span>';
@endphp

<div class="space-y-8">
    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-5 py-4 text-sm font-semibold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    @if($paymentMethods->where('is_active', true)->isEmpty())
        <div class="rounded-lg border border-yellow-300 bg-yellow-50 px-5 py-4 text-sm leading-6 text-yellow-900">
            <span class="font-bold">Belum ada tujuan pembayaran aktif.</span>
            Camaba tidak akan melihat nomor rekening tujuan transfer sampai minimal satu tujuan pembayaran ditambahkan dan diaktifkan.
        </div>
    @endif

    {{-- Form tambah --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm" x-data="{ open: {{ $paymentMethods->isEmpty() || $errors->any() ? 'true' : 'false' }} }">
        <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Tambah Tujuan Pembayaran</h2>
                <p class="mt-1 text-sm text-slate-600">Bisa lebih dari satu, misalnya beberapa rekening bank dan e-wallet.</p>
            </div>
            <button type="button" @click="open = ! open"
                    class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white transition hover:bg-polmind-blue-dark"
                    x-text="open ? 'Tutup Form' : '+ Tambah'">+ Tambah</button>
        </div>

        <form x-show="open" x-cloak action="{{ route('admin.payment-methods.store') }}" method="POST" class="mt-6 grid gap-5 border-t border-slate-200 pt-6 md:grid-cols-2">
            @csrf
            @include('admin.payment-methods.fields', ['method' => null])

            <div class="flex justify-end md:col-span-2">
                <button type="submit" class="rounded-lg bg-polmind-blue px-6 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                    Simpan Tujuan Pembayaran
                </button>
            </div>
        </form>
    </div>

    {{-- Daftar --}}
    <div class="space-y-4">
        @forelse($paymentMethods as $method)
            <div class="rounded-xl border bg-white p-6 shadow-sm {{ $method->is_active ? 'border-slate-200' : 'border-dashed border-slate-300 opacity-70' }}"
                 x-data="{ editing: false }">
                <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-polmind-blue">{{ $method->type_label }}</span>
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $method->is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $method->is_active ? 'Tampil ke camaba' : 'Disembunyikan' }}
                            </span>
                            <span class="text-xs font-semibold text-slate-400">Urutan {{ $method->sort_order }} · dipakai {{ $method->payments_count }} pembayaran</span>
                        </div>
                        <p class="mt-3 text-xl font-bold text-slate-900">{{ $method->provider }}</p>
                        @if($method->account_number)
                            <p class="mt-1 font-mono text-lg font-semibold tracking-wider text-polmind-blue">{{ $method->account_number }}</p>
                        @endif
                        @if($method->account_name)
                            <p class="text-sm text-slate-600">a.n. {{ $method->account_name }}</p>
                        @endif
                        @if($method->instructions)
                            <p class="mt-3 whitespace-pre-line rounded-lg bg-slate-50 p-3 text-sm leading-6 text-slate-600">{{ $method->instructions }}</p>
                        @endif
                    </div>

                    <div class="flex shrink-0 flex-wrap gap-2">
                        <button type="button" @click="editing = ! editing"
                                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            Ubah
                        </button>
                        <form action="{{ route('admin.payment-methods.toggle', $method) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                {{ $method->is_active ? 'Sembunyikan' : 'Tampilkan' }}
                            </button>
                        </form>
                        <form action="{{ route('admin.payment-methods.destroy', $method) }}" method="POST"
                              onsubmit="return confirm('Hapus tujuan pembayaran {{ e($method->provider) }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-xs font-semibold text-red-700 hover:bg-red-100">
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>

                <form x-show="editing" x-cloak action="{{ route('admin.payment-methods.update', $method) }}" method="POST"
                      class="mt-6 grid gap-5 border-t border-slate-200 pt-6 md:grid-cols-2">
                    @csrf
                    @method('PUT')
                    @include('admin.payment-methods.fields', ['method' => $method])

                    <div class="flex justify-end gap-3 md:col-span-2">
                        <button type="button" @click="editing = false" class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">Batal</button>
                        <button type="submit" class="rounded-lg bg-polmind-blue px-6 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">
                Belum ada tujuan pembayaran.
            </div>
        @endforelse
    </div>
</div>
@endsection
