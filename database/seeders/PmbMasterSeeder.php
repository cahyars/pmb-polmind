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
        // Jadwal mengacu pada https://polmind.ac.id/pmb (PMB 2027, TA 2027/2028).
        $pmb2027 = PmbYear::updateOrCreate(
            ['code' => 'PMB2027'],
            [
                'year' => 2027,
                'name' => 'PMB 2027',
                'start_date' => '2026-11-02',
                'end_date' => '2027-07-30',
                'is_active' => true,
                'status' => 'active',
            ]
        );

        $waves = [
            [
                'code' => 'GEL1',
                'name' => 'Gelombang 1',
                'start_date' => '2026-11-02',
                'end_date' => '2027-01-29',
                'registration_fee' => 300000,
                'is_active' => false,
                'status' => 'draft',
            ],
            [
                'code' => 'GEL2',
                'name' => 'Gelombang 2',
                'start_date' => '2027-02-01',
                'end_date' => '2027-04-02',
                'registration_fee' => 300000,
                'is_active' => false,
                'status' => 'draft',
            ],
            [
                'code' => 'GEL3',
                'name' => 'Gelombang 3',
                'start_date' => '2027-04-05',
                'end_date' => '2027-06-04',
                'registration_fee' => 300000,
                'is_active' => false,
                'status' => 'draft',
            ],
            [
                'code' => 'GEL4',
                'name' => 'Gelombang 4',
                'start_date' => '2027-06-07',
                'end_date' => '2027-07-30',
                'registration_fee' => 300000,
                'is_active' => false,
                'status' => 'draft',
            ],
        ];

        foreach ($waves as $wave) {
            AdmissionWave::updateOrCreate(
                [
                    'pmb_year_id' => $pmb2027->id,
                    'code' => $wave['code'],
                ],
                $wave
            );
        }

        // Kode PDDikti dan ID prodi diambil dari SIAKAD (database/data/siakad/prodi.json).
        $siakadPrograms = collect(json_decode(
            file_get_contents(database_path('data/siakad/prodi.json')),
            true
        ))->keyBy('name');

        $programs = [
            [
                'code' => 'TRPL',
                'name' => 'Teknologi Rekayasa Perangkat Lunak',
                'degree' => 'D4',
                'quota' => 40,
                'description' => 'Program studi bidang pengembangan perangkat lunak dengan konsep Teaching Factory.',
                'is_active' => true,
            ],
            [
                'code' => 'TRM',
                'name' => 'Teknologi Rekayasa Manufaktur',
                'degree' => 'D4',
                'quota' => 40,
                'description' => 'Program studi bidang teknologi rekayasa manufaktur untuk kebutuhan industri.',
                'is_active' => true,
            ],
            [
                'code' => 'BD',
                'name' => 'Bisnis Digital',
                'degree' => 'D4',
                'quota' => 40,
                'description' => 'Program studi bidang bisnis berbasis teknologi digital.',
                'is_active' => true,
            ],
        ];

        $programs = array_map(fn (array $program) => $program + [
            'pddikti_code' => $siakadPrograms[$program['name']]['code'] ?? null,
            'siakad_id' => $siakadPrograms[$program['name']]['siakad_id'] ?? null,
        ], $programs);

        foreach ($programs as $program) {
            StudyProgram::updateOrCreate(
                ['code' => $program['code']],
                $program
            );
        }

        $classTypes = [
            [
                'code' => 'REG_A',
                'registration_code' => 'A',
                'name' => 'Reguler A',
                'description' => 'Kelas reguler pagi.',
                'is_active' => true,
            ],
            [
                'code' => 'REG_B',
                'registration_code' => 'B',
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
                'name' => 'Biaya Formulir Pendaftaran',
                'type' => 'registration',
                'amount' => 300000,
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