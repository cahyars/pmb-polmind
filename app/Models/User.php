<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, LogsActivity, Notifiable;

    protected string $activityLabel = 'Akun panel';

    protected array $activityIgnore = ['applicants_seen_at'];

    /**
     * Admin PMB mengelola semuanya. Operator PMB punya akses penuh kecuali
     * Master Data, Tujuan Pembayaran, dan Kelola Akun. Ketua SPMB (PIC tes yang
     * ditugaskan pimpinan) hanya mengelola persiapan seleksi.
     */
    public const ROLES = [
        'admin' => 'Admin PMB',
        'operator' => 'Operator PMB',
        'ketua_spmb' => 'Ketua SPMB',
    ];

    /**
     * Halaman yang dapat dibuka Ketua SPMB (nama route); selebihnya ditolak.
     */
    public const SELECTION_CHAIR_ROUTES = ['admin.spmb.*', 'admin.selections.*', 'admin.documents.file', 'admin.logout', 'admin.pmb-year.select'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'is_active',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'applicants_seen_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSelectionChair(): bool
    {
        return $this->role === 'ketua_spmb';
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? ucfirst((string) $this->role);
    }
}
