<?php

namespace App\Console\Commands;

use App\Models\Applicant;
use App\Services\RegistrationNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Membuat nomor pendaftaran final untuk camaba yang sudah memilih kelas
 * tetapi masih memakai nomor lama/sementara.
 */
class FinalizeRegistrationNumbers extends Command
{
    protected $signature = 'pmb:finalisasi-nomor {--dry-run : Tampilkan perubahan tanpa menyimpan}';

    protected $description = 'Buat nomor pendaftaran final (contoh A120270001) untuk camaba yang sudah memilih kelas';

    public function handle(): int
    {
        $applicants = Applicant::withTrashed()
            ->with(['classType', 'admissionWave', 'pmbYear'])
            ->whereNotNull('class_type_id')
            ->orderBy('id')
            ->get()
            ->reject(fn (Applicant $applicant) => RegistrationNumber::isFinal($applicant->registration_number));

        if ($applicants->isEmpty()) {
            $this->info('Semua nomor pendaftaran sudah final.');

            return self::SUCCESS;
        }

        foreach ($applicants as $applicant) {
            $old = $applicant->registration_number;

            if ($this->option('dry-run')) {
                $this->line("{$old} ({$applicant->full_name}) akan diberi nomor final kelas {$applicant->classType->code}");

                continue;
            }

            $new = DB::transaction(function () use ($applicant) {
                $number = RegistrationNumber::final($applicant, $applicant->classType);
                $applicant->update(['registration_number' => $number]);

                return $number;
            });

            $this->info("{$old} => {$new} ({$applicant->full_name})");
        }

        return self::SUCCESS;
    }
}
