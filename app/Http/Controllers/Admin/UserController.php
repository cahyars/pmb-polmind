<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Kelola akun panel PMB (hanya Admin PMB). Akun tidak dihapus agar jejak
 * verifikasi tetap terbaca; akun yang tidak dipakai cukup dinonaktifkan.
 */
class UserController extends Controller
{
    public function index()
    {
        $users = User::orderByRaw("role = 'admin' desc")->orderBy('name')->get();

        return view('admin.users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), [], $this->attributes());

        User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'role' => $validated['role'],
            'password' => $validated['password'],
            'is_active' => true,
        ]);

        return back()->with('success', 'Akun ' . $validated['name'] . ' berhasil dibuat.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate($this->rules($user), [], $this->attributes());

        if ($user->isAdmin() && $validated['role'] !== 'admin') {
            if ($user->is($request->user('web'))) {
                return back()->withErrors('Anda tidak dapat menurunkan peran akun sendiri.');
            }

            if ($this->isLastActiveAdmin($user)) {
                return back()->withErrors('Minimal harus ada satu Admin PMB yang aktif.');
            }
        }

        $user->update(array_filter([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'role' => $validated['role'],
            'password' => $validated['password'] ?? null,
        ], fn ($value) => $value !== null));

        return back()->with('success', 'Akun ' . $user->name . ' berhasil diperbarui.');
    }

    public function toggle(Request $request, User $user)
    {
        if ($user->is($request->user('web'))) {
            return back()->withErrors('Anda tidak dapat menonaktifkan akun sendiri.');
        }

        if ($user->is_active && $user->isAdmin() && $this->isLastActiveAdmin($user)) {
            return back()->withErrors('Minimal harus ada satu Admin PMB yang aktif.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', 'Akun ' . $user->name . ($user->is_active ? ' diaktifkan.' : ' dinonaktifkan.'));
    }

    private function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ];
    }

    private function attributes(): array
    {
        return [
            'name' => 'nama',
            'role' => 'peran',
        ];
    }

    private function isLastActiveAdmin(User $user): bool
    {
        return ! User::where('role', 'admin')->where('is_active', true)->whereKeyNot($user->id)->exists();
    }
}
