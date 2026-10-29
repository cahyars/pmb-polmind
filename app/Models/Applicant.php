<?php

namespace App\Models;

use App\Services\SequenceRewinder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class Applicant extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Jalur pendaftaran yang dipilih camaba saat registrasi.
     */
    public const REGISTRATION_PATHS = [
        'umum' => [
            'label' => 'Umum',
            'description' => 'Untuk lulusan SMA/SMK/MA atau sederajat. Mengikuti ujian seleksi sesuai jadwal gelombang.',
        ],
        'prestasi' => [
            'label' => 'Prestasi',
            'description' => 'Untuk pendaftar dengan prestasi akademik maupun non-akademik. Lampirkan sertifikat penghargaan.',
        ],
        'undangan' => [
            'label' => 'Undangan',
            'description' => 'Untuk siswa yang mendapat rekomendasi atau undangan dari sekolah asal.',
        ],
    ];

    protected $fillable = [
        'pmb_year_id',
        'admission_wave_id',
        'study_program_id',
        'second_study_program_id',
        'accepted_study_program_id',
        'class_type_id',

        'registration_number',
        'full_name',
        'email',
        'password',
        'phone',

        'nik',
        'nisn',
        'npwp',
        'gender',
        'birth_place',
        'birth_date',
        'religion_id',
        'citizenship',
        'home_phone',
        'transportation_id',
        'kps_recipient',
        'kps_number',
        'special_need_id',

        'source_information',
        'registration_path',

        'registration_status',
        'document_status',
        'payment_status',
        'selection_status',
        're_registration_status',
        'sync_status',

        'nim',
        'nim_generated_at',
        'synced_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'religion_id' => 'integer',
        'transportation_id' => 'integer',
        'kps_recipient' => 'boolean',
        'special_need_id' => 'integer',
        'nim_generated_at' => 'datetime',
        'synced_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * 1 NIK hanya boleh mendaftar sekali dalam satu gelombang,
     * tetapi boleh mendaftar lagi di gelombang berbeda.
     */
    public static function nikRules(?int $admissionWaveId, ?int $ignoreId = null): array
    {
        return [
            'required',
            'digits:16',
            Rule::unique('applicants', 'nik')
                ->where('admission_wave_id', $admissionWaveId)
                ->ignore($ignoreId),
        ];
    }

    public static function nikMessages(?AdmissionWave $wave): array
    {
        return [
            'nik.digits' => 'NIK harus 16 digit angka sesuai KTP/KK.',
            'nik.unique' => 'NIK ini sudah terdaftar di ' . ($wave?->name ?? 'gelombang ini')
                . '. Satu NIK hanya bisa mendaftar satu kali per gelombang. Silakan login dengan akun yang sudah dibuat.',
        ];
    }

    /**
     * Hanya peminat (belum bayar atau bukti bayar ditolak) yang boleh dihapus admin,
     * agar data yang sudah punya pembayaran atau proses lanjutan tidak hilang.
     */
    public function isDeletable(): bool
    {
        return in_array($this->payment_status, ['belum_bayar', 'ditolak', null], true)
            && blank($this->nim);
    }

    /**
     * Hapus permanen beserta tagihan, pembayaran, dan file upload, lalu mundurkan
     * nomor urut agar nomor pendaftar, invoice, dan pembayaran bisa dipakai lagi.
     */
    public function purge(): void
    {
        $files = $this->payments()->pluck('proof_file_path')
            ->merge($this->documents()->pluck('file_path'))
            ->filter()
            ->all();

        $pmbYear = $this->pmbYear;

        $this->forceDelete();

        Storage::disk('local')->delete($files);

        if ($pmbYear) {
            SequenceRewinder::rewind($pmbYear);
        }
    }

    /**
     * Kartu ujian terbuka setelah formulir lunas (atau digratiskan) dan biodata lengkap.
     */
    public function canPrintExamCard(): bool
    {
        return $this->payment_status === 'valid'
            && $this->registration_status === 'biodata_lengkap';
    }

    public function getGenderLabelAttribute(): string
    {
        return match ($this->gender) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            default => '-',
        };
    }

    public function getReligionNameAttribute(): ?string
    {
        return RefOption::nameOf(RefOption::AGAMA, $this->religion_id);
    }

    public function getRegistrationPathLabelAttribute(): string
    {
        return self::REGISTRATION_PATHS[$this->registration_path]['label'] ?? self::REGISTRATION_PATHS['umum']['label'];
    }

    /**
     * Pilihan prodi (1 atau 2) tempat camaba diterima, null jika belum diterima.
     */
    public function acceptedChoice(): ?int
    {
        if ($this->selection_status !== 'diterima') {
            return null;
        }

        return $this->accepted_study_program_id
            && $this->accepted_study_program_id === $this->second_study_program_id ? 2 : 1;
    }

    /**
     * Prodi yang dijalani camaba: prodi tempat diterima, atau pilihan pertama selama belum diterima.
     * Dipakai untuk NIM, daftar ulang, dan sinkron SIAKAD.
     */
    public function getEnrolledStudyProgramAttribute(): ?StudyProgram
    {
        return ($this->accepted_study_program_id ? $this->acceptedStudyProgram : null) ?? $this->studyProgram;
    }

    public function scopeWhereEnrolledProgram($query, $studyProgramId)
    {
        return $query->where(function ($q) use ($studyProgramId) {
            $q->where('accepted_study_program_id', $studyProgramId)
                ->orWhere(fn ($legacy) => $legacy->whereNull('accepted_study_program_id')->where('study_program_id', $studyProgramId));
        });
    }

    public function pmbYear()
    {
        return $this->belongsTo(PmbYear::class);
    }

    public function admissionWave()
    {
        return $this->belongsTo(AdmissionWave::class);
    }

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function secondStudyProgram()
    {
        return $this->belongsTo(StudyProgram::class, 'second_study_program_id');
    }

    public function acceptedStudyProgram()
    {
        return $this->belongsTo(StudyProgram::class, 'accepted_study_program_id');
    }

    public function classType()
    {
        return $this->belongsTo(ClassType::class);
    }

    public function address()
    {
        return $this->hasOne(ApplicantAddress::class);
    }

    public function education()
    {
        return $this->hasOne(ApplicantEducation::class);
    }

    public function parentData()
    {
        return $this->hasOne(ApplicantParent::class);
    }

    /**
     * Pas foto camaba, dipakai sebagai foto profil.
     */
    public function photoDocument()
    {
        return $this->hasOne(ApplicantDocument::class)
            ->whereNotNull('file_path')
            ->whereHas('documentType', fn ($query) => $query->where('code', 'PAS_FOTO'));
    }

    public function documents()
    {
        return $this->hasMany(ApplicantDocument::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function registrationInvoice()
    {
        return $this->hasOne(Invoice::class)->where('type', 'registration');
    }

    public function reRegistrationInvoice()
    {
        return $this->hasOne(Invoice::class)->where('type', 're_registration');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function selection()
    {
        return $this->hasOne(Selection::class);
    }

    public function reRegistration()
    {
        return $this->hasOne(ReRegistration::class);
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class);
    }

    public function latestFollowUp()
    {
        return $this->hasOne(FollowUp::class)->latestOfMany();
    }

    public function integrationLogs()
    {
        return $this->hasMany(IntegrationLog::class);
    }
}