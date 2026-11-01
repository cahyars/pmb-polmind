@extends('layouts.admin')

@section('title', 'Log Aktivitas')
@section('page_title', 'Kelola Akun')
@section('page_subtitle', 'Log Aktivitas: siapa melakukan apa dan kapan, beserta perubahan datanya.')

@section('content')
@php
    $input = 'mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-polmind-blue focus:ring-4 focus:ring-blue-100';
    $selectedUser = $users->firstWhere('id', (int) ($filters['user'] ?? 0));
@endphp

<div class="space-y-6">
    @include('admin.users.partials.tabs')

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('admin.activity-logs.index') }}" method="GET" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
            <div>
                <label for="user" class="text-xs font-semibold text-slate-600">Akun</label>
                <select id="user" name="user" class="{{ $input }}">
                    <option value="">Semua akun</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected($selectedUser?->is($user))>{{ $user->name }} ({{ $user->role_label }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="actor" class="text-xs font-semibold text-slate-600">Pelaku</label>
                <select id="actor" name="actor" class="{{ $input }}">
                    <option value="">Semua pelaku</option>
                    @foreach(\App\Models\ActivityLog::ACTOR_TYPES as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['actor'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="group" class="text-xs font-semibold text-slate-600">Jenis aksi</label>
                <select id="group" name="group" class="{{ $input }}">
                    <option value="">Semua aksi</option>
                    @foreach(\App\Http\Controllers\Admin\ActivityLogController::GROUPS as $value => $group)
                        <option value="{{ $value }}" @selected(($filters['group'] ?? '') === $value)>{{ $group['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="from" class="text-xs font-semibold text-slate-600">Dari tanggal</label>
                <input type="date" id="from" name="from" value="{{ $filters['from'] ?? '' }}" class="{{ $input }}">
            </div>
            <div>
                <label for="to" class="text-xs font-semibold text-slate-600">Sampai</label>
                <input type="date" id="to" name="to" value="{{ $filters['to'] ?? '' }}" class="{{ $input }}">
            </div>
            <div>
                <label for="keyword" class="text-xs font-semibold text-slate-600">Kata kunci</label>
                <input type="text" id="keyword" name="keyword" value="{{ $filters['keyword'] ?? '' }}" maxlength="100"
                       placeholder="ID pendaftar, nama, aksi" class="{{ $input }}">
            </div>
        </div>
        <div class="mt-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <p class="text-xs leading-5 text-slate-500">
                Log tidak dapat diubah atau dihapus. Password dan PIN tidak pernah disimpan, hanya ditandai "(diubah)".
            </p>
            <div class="flex shrink-0 gap-2">
                @if($filters)
                    <a href="{{ route('admin.activity-logs.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Reset</a>
                @endif
                <button type="submit" class="rounded-lg bg-polmind-blue px-5 py-2.5 text-sm font-semibold text-white">Terapkan</button>
            </div>
        </div>
    </form>

    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-1 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center">
            <h2 class="text-base font-semibold text-slate-900">
                {{ $selectedUser ? 'Aktivitas ' . $selectedUser->name : 'Semua Aktivitas' }}
            </h2>
            <p class="text-xs text-slate-500">{{ number_format($logs->total(), 0, ',', '.') }} catatan · terbaru di atas</p>
        </div>

        @include('admin.activity-logs.partials.table', ['logs' => $logs])

        @if($logs->total() > min(\App\Http\Controllers\Controller::PER_PAGE_OPTIONS))
            <div class="border-t border-slate-200 px-5 py-4"><x-pagination :paginator="$logs" /></div>
        @endif
    </section>
</div>
@endsection
