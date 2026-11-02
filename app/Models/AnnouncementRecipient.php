<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Camaba penerima satu pengumuman beserta waktu dibaca.
 */
class AnnouncementRecipient extends Model
{
    protected $fillable = [
        'announcement_id',
        'applicant_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }
}
