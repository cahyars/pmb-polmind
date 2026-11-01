<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Satu baris jejak aktivitas. Bersifat tambah-saja: tidak bisa diubah atau dihapus dari aplikasi
 * agar dapat dipakai sebagai bukti pertanggungjawaban.
 */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    public const ACTOR_TYPES = [
        'user' => 'Admin / Operator',
        'applicant' => 'Camaba',
        'guest' => 'Login gagal',
        'system' => 'Sistem',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Log aktivitas tidak dapat diubah.'));
        static::deleting(fn () => throw new LogicException('Log aktivitas tidak dapat dihapus.'));
    }

    public function applicant()
    {
        return $this->belongsTo(Applicant::class)->withoutGlobalScopes();
    }
}
