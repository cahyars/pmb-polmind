<?php

namespace App\Console\Commands;

use App\Models\Applicant;
use App\Models\PmbYear;
use App\Services\AutoAnnouncements;
use Illuminate\Console\Command;

/**
 * Mengirim pengumuman pengambilan kaos ke camaba Tahun PMB aktif yang pembayaran formulirnya
 * sudah valid sebelum pengumuman otomatis berlaku. Aman dijalankan ulang.
 */
class SendShirtPickupAnnouncements extends Command
{
    protected $signature = 'pmb:pengumuman-kaos {--dry-run : Tampilkan jumlah penerima tanpa mengirim}';

    protected $description = 'Kirim pengumuman pengambilan kaos ke camaba yang pembayaran formulirnya sudah valid';

    public function handle(): int
    {
        foreach (PmbYear::where('is_active', true)->get() as $pmbYear) {
            $applicantIds = Applicant::where('pmb_year_id', $pmbYear->id)
                ->where('payment_status', 'valid')
                ->pluck('id')
                ->all();

            if ($this->option('dry-run')) {
                $this->line("Tahun PMB {$pmbYear->year}: " . count($applicantIds) . ' camaba valid.');

                continue;
            }

            $added = $applicantIds ? AutoAnnouncements::deliver(AutoAnnouncements::SHIRT_PICKUP, $pmbYear->id, $applicantIds) : 0;

            $this->info("Tahun PMB {$pmbYear->year}: {$added} camaba baru menerima pengumuman (dari " . count($applicantIds) . ' camaba valid).');
        }

        return self::SUCCESS;
    }
}
