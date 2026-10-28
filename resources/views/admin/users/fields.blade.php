{{-- Isian akun; $user null untuk form tambah --}}
@php
    // Isian lama hanya dipakai oleh form yang baru saja dikirim.
    $useOld = $user ? (int) old('_user_id') === $user->id : ! old('_user_id');
    $fieldValue = fn ($field, $default = null) => $useOld ? old($field, $default) : $default;
@endphp
<div>
    <label class="{{ $label }}">Nama</label>
    <input type="text" name="name" value="{{ $fieldValue('name', $user?->name) }}" required maxlength="255" class="{{ $input }}">
</div>

<div>
    <label class="{{ $label }}">Email login</label>
    <input type="email" name="email" value="{{ $fieldValue('email', $user?->email) }}" required maxlength="255" class="{{ $input }}">
</div>

<div>
    <label class="{{ $label }}">Peran</label>
    <select name="role" required class="{{ $input }}">
        @foreach(\App\Models\User::ROLES as $value => $roleLabel)
            <option value="{{ $value }}" @selected($fieldValue('role', $user?->role ?? 'operator') === $value)>{{ $roleLabel }}</option>
        @endforeach
    </select>
</div>

<div class="grid gap-4 sm:grid-cols-2 md:col-span-1">
    <div>
        <label class="{{ $label }}">Password {!! $user ? '<span class="font-semibold text-slate-400">(isi jika diganti)</span>' : '' !!}</label>
        <input type="password" name="password" @if(! $user) required @endif minlength="8" autocomplete="new-password" class="{{ $input }}">
    </div>
    <div>
        <label class="{{ $label }}">Ulangi password</label>
        <input type="password" name="password_confirmation" @if(! $user) required @endif minlength="8" autocomplete="new-password" class="{{ $input }}">
    </div>
</div>
