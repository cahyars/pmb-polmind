<?php

namespace App\Services;

use App\Models\Applicant;
use Illuminate\Database\Eloquent\Builder;

/**
 * Kategori camaba penerima pengumuman, diturunkan dari status pembayaran, biodata,
 * seleksi, dan daftar ulang. Beberapa kategori digabung dengan OR.
 */
class AnnouncementAudience
{
    public const SEGMENTS = [
        'semua' => ['label' => 'Semua Camaba', 'description' => 'Seluruh akun camaba pada Tahun PMB ini.'],
        'peminat' => ['label' => 'Peminat', 'description' => 'Belum bayar formulir atau bukti bayarnya ditolak.'],
        'menunggu_bayar' => ['label' => 'Menunggu Verifikasi Bayar', 'description' => 'Bukti bayar formulir terkirim, belum diverifikasi.'],
        'biodata_belum_lengkap' => ['label' => 'Sudah Bayar, Biodata Belum Lengkap', 'description' => 'Formulir lunas/gratis, biodata belum lengkap.'],
        'tahap_seleksi' => ['label' => 'Tahap Seleksi', 'description' => 'Biodata lengkap, hasil seleksi belum diumumkan.'],
        'diterima' => ['label' => 'Lolos Seleksi', 'description' => 'Dinyatakan diterima.'],
        'cadangan' => ['label' => 'Cadangan', 'description' => 'Hasil seleksi cadangan.'],
        'ditolak' => ['label' => 'Tidak Lulus', 'description' => 'Dinyatakan tidak lulus seleksi.'],
        'belum_daftar_ulang' => ['label' => 'Belum Daftar Ulang', 'description' => 'Diterima, daftar ulang belum valid.'],
        'sudah_daftar_ulang' => ['label' => 'Sudah Daftar Ulang', 'description' => 'Daftar ulang sudah valid.'],
    ];

    /**
     * Query camaba penerima. Di panel admin, Applicant sudah dibatasi Tahun PMB terpilih.
     *
     * @param  list<string>  $segments
     */
    public static function query(array $segments, ?int $admissionWaveId = null, ?int $studyProgramId = null): Builder
    {
        return Applicant::query()
            ->unless(in_array('semua', $segments, true), fn ($query) => $query->where(function ($query) use ($segments) {
                foreach ($segments as $segment) {
                    $query->orWhere(fn ($query) => self::apply($query, $segment));
                }
            }))
            ->when($admissionWaveId, fn ($query) => $query->where('admission_wave_id', $admissionWaveId))
            ->when($studyProgramId, fn ($query) => $query->whereEnrolledProgram($studyProgramId));
    }

    private static function apply(Builder $query, string $segment): void
    {
        $validReRegistration = fn ($reRegistration) => $reRegistration->where('status', 'valid');
        $biodataIncomplete = fn ($query) => $query->where('registration_status', '!=', 'biodata_lengkap')->orWhereNull('registration_status');

        match ($segment) {
            'peminat' => $query->where(fn ($query) => $query->whereIn('payment_status', ['belum_bayar', 'ditolak'])->orWhereNull('payment_status')),
            'menunggu_bayar' => $query->where('payment_status', 'menunggu_verifikasi'),
            'biodata_belum_lengkap' => $query->where('payment_status', 'valid')->where($biodataIncomplete),
            'tahap_seleksi' => $query->where('payment_status', 'valid')
                ->where('registration_status', 'biodata_lengkap')
                ->where(fn ($query) => $query->where('selection_status', 'belum_diseleksi')->orWhereNull('selection_status')),
            'diterima', 'cadangan', 'ditolak' => $query->where('selection_status', $segment),
            'belum_daftar_ulang' => $query->where('selection_status', 'diterima')->whereDoesntHave('reRegistration', $validReRegistration),
            'sudah_daftar_ulang' => $query->whereHas('reRegistration', $validReRegistration),
        };
    }
}
