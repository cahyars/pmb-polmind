@extends('layouts.admin')

@section('title', 'Kelola Akun')
@section('page_title', 'Kelola Akun')
@section('page_subtitle', 'Akun Admin dan Operator PMB yang dapat masuk ke panel admin.')

@section('content')
@php
    $input = 'mt-2 w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100';
    $label = 'text-sm font-semibold text-slate-700';
    $currentUser = auth('web')->user();
@endphp

<div class="space-y-8">
    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-5 py-4 text-sm font-semibold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        @foreach(\App\Models\User::ROLES as $role => $roleLabel)
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-900">{{ $roleLabel }}</p>
                <p class="mt-1 text-sm leading-6 text-slate-600">
                    @if($role === 'admin')
                        Akses penuh, termasuk Master Data, Tujuan Pembayaran, dan Kelola Akun.
                    @else
                        Akses penuh ke pendaftar, verifikasi, seleksi, daftar ulang, laporan, dan brosur. Tidak dapat mengelola Master Data, Tujuan Pembayaran, dan akun lain.
                    @endif
                </p>
                <p class="mt-3 text-xs font-semibold text-slate-500">{{ $users->where('role', $role)->where('is_active', true)->count() }} akun aktif</p>
            </div>
        @endforeach
    </div>

    {{-- Form tambah --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm" x-data="{ open: @js($errors->any() && ! old('_user_id')) }">
        <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Tambah Akun</h2>
                <p class="mt-1 text-sm text-slate-600">Bagikan email dan password ke pemilik akun, lalu minta segera menggantinya.</p>
            </div>
            <button type="button" @click="open = ! open"
                    class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white transition hover:bg-polmind-blue-dark"
                    x-text="open ? 'Tutup Form' : '+ Tambah Akun'">+ Tambah Akun</button>
        </div>

        <form x-show="open" x-cloak action="{{ route('admin.users.store') }}" method="POST" class="mt-6 grid gap-5 border-t border-slate-200 pt-6 md:grid-cols-2">
            @csrf
            @include('admin.users.fields', ['user' => null])

            <div class="flex justify-end md:col-span-2">
                <button type="submit" class="rounded-lg bg-polmind-blue px-6 py-3 text-sm font-bold text-white transition hover:bg-polmind-blue-dark">
                    Simpan Akun
                </button>
            </div>
        </form>
    </div>

    {{-- Daftar akun --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="border-b border-slate-200 text-xs text-slate-400 [&_th]:px-5 [&_th]:py-3.5 [&_th]:font-medium">
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Peran</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="table-zebra">
                    @foreach($users as $user)
                        @php($isSelf = $user->is($currentUser))
                        <tr>
                            <td class="px-5 py-3.5 text-slate-500">{{ $loop->iteration }}</td>
                            <td class="px-5 py-3.5 font-semibold text-slate-900">
                                {{ $user->name }}
                                @if($isSelf)
                                    <span class="ml-1 text-xs font-medium text-slate-500">(Anda)</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-slate-600">{{ $user->email }}</td>
                            <td class="px-5 py-3.5">
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $user->isAdmin() ? 'bg-blue-100 text-polmind-blue' : 'bg-slate-100 text-slate-700' }}">{{ $user->role_label }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex justify-end gap-2">
                                    <button type="button"
                                            onclick="document.getElementById('edit-user-{{ $user->id }}').classList.toggle('hidden')"
                                            class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-polmind-blue">Edit</button>
                                    @unless($isSelf)
                                        <form action="{{ route('admin.users.toggle', $user) }}" method="POST"
                                              onsubmit="return confirm(@js(($user->is_active ? 'Nonaktifkan' : 'Aktifkan') . ' akun ' . $user->name . '?'))">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">
                                                {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                        <tr id="edit-user-{{ $user->id }}" class="{{ (int) old('_user_id') === $user->id ? '' : 'hidden' }} bg-slate-50">
                            <td colspan="6" class="px-6 py-5">
                                <form action="{{ route('admin.users.update', $user) }}" method="POST" class="grid gap-5 md:grid-cols-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="_user_id" value="{{ $user->id }}">
                                    @include('admin.users.fields', ['user' => $user])
                                    <div class="flex justify-end gap-3 md:col-span-2">
                                        <button type="button"
                                                onclick="document.getElementById('edit-user-{{ $user->id }}').classList.add('hidden')"
                                                class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">Batal</button>
                                        <button type="submit" class="rounded-lg bg-polmind-blue px-5 py-3 text-sm font-semibold text-white">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
