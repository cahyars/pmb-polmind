<?php

namespace App\Console\Commands;

use App\Models\Applicant;
use Illuminate\Console\Command;

/**
 * Menghapus permanen camaba yang dulu dihapus admin sebagai soft delete,
 * agar nomor pendaftar, invoice, dan pembayarannya bisa dipakai lagi.
 */
class PurgeDeletedApplicants extends Command
{
    protected $signature = 'pmb:hapus-permanen {--dry-run : Tampilkan data yang akan dihapus tanpa menghapus}';

    protected $description = 'Hapus permanen camaba yang sudah dihapus admin lalu mundurkan nomor urut';

    public function handle(): int
    {
        $applicants = Applicant::onlyTrashed()->orderBy('id')->get();

        if ($applicants->isEmpty()) {
            $this->info('Tidak ada camaba terhapus yang perlu dibersihkan.');

            return self::SUCCESS;
        }

        foreach ($applicants as $applicant) {
            $label = "{$applicant->registration_number} ({$applicant->full_name})";

            if ($this->option('dry-run')) {
                $this->line("{$label} akan dihapus permanen");

                continue;
            }

            $applicant->purge();

            $this->info("{$label} dihapus permanen");
        }

        return self::SUCCESS;
    }
}
