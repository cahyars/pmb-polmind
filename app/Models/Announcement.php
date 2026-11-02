<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Services\AnnouncementAudience;
use App\Services\AutoAnnouncements;
use Illuminate\Database\Eloquent\Model;

/**
 * Pengumuman admin yang dikirim ke kategori camaba tertentu dan tampil di lonceng portal camaba.
 */
class Announcement extends Model
{
    use LogsActivity;

    protected string $activityLabel = 'Pengumuman';

    protected array $activityIgnore = ['recipient_count'];

    protected $fillable = [
        'pmb_year_id',
        'auto_key',
        'title',
        'body',
        'link_url',
        'link_label',
        'audiences',
        'admission_wave_id',
        'study_program_id',
        'recipient_count',
        'created_by',
    ];

    protected $casts = [
        'audiences' => 'array',
        'recipient_count' => 'integer',
    ];

    public function recipients()
    {
        return $this->hasMany(AnnouncementRecipient::class);
    }

    public function applicants()
    {
        return $this->belongsToMany(Applicant::class, 'announcement_recipients')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    public function admissionWave()
    {
        return $this->belongsTo(AdmissionWave::class);
    }

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Ringkasan target untuk tabel admin, contoh "Peminat, Tahap Seleksi · Gelombang 1 · TRPL".
     */
    public function getAudienceLabelAttribute(): string
    {
        if ($this->auto_key) {
            return AutoAnnouncements::AUDIENCE_LABELS[$this->auto_key] ?? 'Otomatis';
        }

        return collect([
            collect($this->audiences)->map(fn ($key) => AnnouncementAudience::SEGMENTS[$key]['label'] ?? $key)->implode(', '),
            $this->admissionWave?->name,
            $this->studyProgram?->name,
        ])->filter()->implode(' · ');
    }
}
