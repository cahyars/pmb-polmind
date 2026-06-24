<?php

namespace Database\Seeders;

use App\Models\AdmissionWave;
use App\Models\ClassType;
use App\Models\DocumentType;
use App\Models\FeeComponent;
use App\Models\PmbYear;
use App\Models\StudyProgram;
use Illuminate\Database\Seeder;

class PmbMasterSeeder extends Seeder
{
    public function run(): void
    {
        $pmb2026 = PmbYear::updateOrCreate(
            ['code' => 'PMB2026'],
            [
                'year' => 2026,
                'name' => 'PMB 2026',
                'start_date' => '2026-01-01',
                'end_date' => '2026-08-31',
                'is_active' => true,
                'status' => 'active',
            ]
        );

        PmbYear::updateOrCreate(
            ['code' => 'PMB2025'],
            [
                'year' => 2025,
                'name' => 'PMB 2025',
                'start_date' => '2025-01-01',
                'end_date' => '2025-08-31',
                'is_active' => false,
                'status' => 'archived',
            ]
        );

        $waves = [
            [
                'code' => 'GEL1',
                'name' => 'Gelombang 1',
                'start_date' => '2026-01-01',
                'end_date' => '2026-03-31',
                'registration_fee' => 350000,
                'is_active' => false,
                'status' => 'closed',
            ],
            [
                'code' => 'GEL2',
                'name' => 'Gelombang 2',
                'start_date' => '2026-04-01',
                'end_date' => '2026-06-30',
                'registration_fee' => 350000,
                'is_active' => true,
                'status' => 'open',
            ],
            [
                'code' => 'GEL3',
                'name' => 'Gelombang 3',
                'start_date' => '2026-07-01',
                'end_date' => '2026-08-31',
                'registration_fee' => 350000,
                'is_active' => false,
                'status' => 'draft',
            ],
        ];

        foreach ($waves as $wave) {
            AdmissionWave::updateOrCreate(
                [
                    'pmb_year_id' => $pmb2026->id,
                    'code' => $wave['code'],
                ],
                $wave
            );
        }

        $programs = [
            [
                'code' => 'TRPL',
                'name' => 'Teknologi Rekayasa Perangkat Lunak',
                'degree' => 'D4',
                'quota' => 40,
                'description' => 'Program studi bidang pengembangan perangkat lunak, sistem informasi, dan teknologi digital.',
                'is_active' => true,
            ],
            [
                'code' => 'TRM',
                'name' => 'Teknologi Rekayasa Manufaktur',
                'degree' => 'D4',
                'quota' => 40,
                'description' => 'Program studi bidang manufaktur, otomasi, proses produksi, dan teknologi industri.',
                'is_active' => true,
            ],
            [
                'code' => 'BD',
                'name' => 'Bisnis Digital',
                'degree' => 'D4',
                'quota' => 40,
                'description' => 'Program studi bidang bisnis berbasis digital, pemasaran digital, dan kewirausahaan.',
                'is_active' => true,
            ],
        ];

        foreach ($programs as $program) {
            StudyProgram::updateOrCreate(
                ['code' => $program['code']],
                $program
            );
        }

        $classTypes = [
            [
                'code' => 'REG_A',
                'name' => 'Reguler A',
                'description' => 'Kelas reguler pagi.',
                'is_active' => true,
            ],
            [
                'code' => 'REG_B',
                'name' => 'Reguler B',
                'description' => 'Kelas karyawan atau malam.',
                'is_active' => true,
            ],
        ];

        foreach ($classTypes as $classType) {
            ClassType::updateOrCreate(
                ['code' => $classType['code']],
                $classType
            );
        }

        $documentTypes = [
            [
                'code' => 'PAS_FOTO',
                'name' => 'Pas Foto',
                'is_required' => true,
                'allowed_extensions' => ['jpg', 'jpeg', 'png'],
                'max_size_kb' => 2048,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'IDENTITAS',
                'name' => 'KTP / Kartu Pelajar',
                'is_required' => true,
                'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
                'max_size_kb' => 2048,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'KK',
                'name' => 'Kartu Keluarga',
                'is_required' => true,
                'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
                'max_size_kb' => 2048,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'IJAZAH_SKL',
                'name' => 'Ijazah / SKL',
                'is_required' => true,
                'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
                'max_size_kb' => 4096,
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'code' => 'RAPOR',
                'name' => 'Rapor',
                'is_required' => false,
                'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
                'max_size_kb' => 4096,
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'SERTIFIKAT',
                'name' => 'Sertifikat Prestasi',
                'is_required' => false,
                'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
                'max_size_kb' => 4096,
                'sort_order' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($documentTypes as $documentType) {
            DocumentType::updateOrCreate(
                ['code' => $documentType['code']],
                $documentType
            );
        }

        $fees = [
            [
                'code' => 'PENDAFTARAN',
                'name' => 'Biaya Pendaftaran',
                'type' => 'registration',
                'amount' => 350000,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'DAFTAR_ULANG',
                'name' => 'Daftar Ulang',
                'type' => 're_registration',
                'amount' => 2500000,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'SPI_AWAL',
                'name' => 'Angsuran SPI',
                'type' => 're_registration',
                'amount' => 5000000,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'SPP_SEMESTER_1',
                'name' => 'SPP Semester 1',
                'type' => 're_registration',
                'amount' => 4000000,
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($fees as $fee) {
            FeeComponent::updateOrCreate(
                ['code' => $fee['code']],
                $fee
            );
        }
    }
}