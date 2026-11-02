<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Applicant;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Pengumuman otomatis dari sistem. Satu pengumuman per Tahun PMB; camaba ditambahkan
 * sebagai penerima saat memenuhi syaratnya, sehingga statistik baca tetap di satu tempat.
 */
class AutoAnnouncements
{
    public const SHIRT_PICKUP = 'ambil_kaos';

    public const AUDIENCE_LABELS = [
        self::SHIRT_PICKUP => 'Otomatis: setiap camaba yang pembayaran formulirnya valid (termasuk gratis)',
    ];

    /**
     * Dipanggil saat pembayaran formulir camaba menjadi valid (dibayar atau dibebaskan).
     * Kegagalan hanya dilaporkan agar verifikasi pembayaran tetap berjalan.
     */
    public static function shirtPickup(Applicant $applicant): void
    {
        if (! $applicant->pmb_year_id) {
            return;
        }

        try {
            self::deliver(self::SHIRT_PICKUP, $applicant->pmb_year_id, [$applicant->id]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Tambahkan penerima; camaba yang sudah menerima dilewati.
     *
     * @param  list<int>  $applicantIds
     * @return int jumlah penerima baru
     */
    public static function deliver(string $key, int $pmbYearId, array $applicantIds): int
    {
        $announcement = Announcement::createOrFirst(
            ['auto_key' => $key, 'pmb_year_id' => $pmbYearId],
            self::content($key) + ['audiences' => []]
        );

        $now = now();
        $added = 0;

        foreach (array_chunk($applicantIds, 500) as $chunk) {
            $added += DB::table('announcement_recipients')->insertOrIgnore(array_map(fn ($applicantId) => [
                'announcement_id' => $announcement->id,
                'applicant_id' => $applicantId,
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk));
        }

        if ($added > 0) {
            $announcement->increment('recipient_count', $added);
        }

        return $added;
    }

    private static function content(string $key): array
    {
        return match ($key) {
            self::SHIRT_PICKUP => [
                'title' => 'Pengambilan Kaos PMB',
                'body' => "Selamat, pembayaran formulir pendaftaran Anda sudah valid.\n\n"
                    . "Silakan mengambil kaos di kampus Politeknik Mitra Industri, Lantai P1 Apartemen Vasanta Innopark, Kawasan Industri MM2100.\n\n"
                    . "Waktu pengambilan: hari kerja Senin-Jumat, pukul 08.00 - 16.00 WIB.\n\n"
                    . 'Info selengkapnya hubungi Admin PMB.',
                'link_url' => AdminWhatsApp::url('Halo Admin PMB Polmind, saya ingin bertanya tentang pengambilan kaos PMB.'),
                'link_label' => 'Hubungi Admin PMB',
            ],
        };
    }
}
