<?php

namespace Database\Seeders;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\ClassType;
use App\Models\PmbYear;
use App\Models\StudyProgram;
use Illuminate\Database\Seeder;

class PmbDemoApplicantSeeder extends Seeder
{
    public function run(): void
    {
        $pmbYear = PmbYear::where('code', 'PMB2026')->first();
        $wave = AdmissionWave::where('code', 'GEL2')->first();
        $program = StudyProgram::where('code', 'TRPL')->first();
        $secondProgram = StudyProgram::where('code', 'BD')->first();
        $classType = ClassType::where('code', 'REG_A')->first();

        if (! $pmbYear || ! $wave || ! $program || ! $classType) {
            return;
        }

        $applicant = Applicant::updateOrCreate(
            ['registration_number' => 'PMB20260001'],
            [
                'pmb_year_id' => $pmbYear->id,
                'admission_wave_id' => $wave->id,
                'study_program_id' => $program->id,
                'second_study_program_id' => $secondProgram?->id,
                'class_type_id' => $classType->id,

                'full_name' => 'Ahmad Fauzi',
                'email' => 'ahmad.fauzi@example.com',
                'password' => 'password',
                'phone' => '081234567890',

                'nik' => '3216000000000001',
                'nisn' => '0061234567',
                'gender' => 'male',
                'birth_place' => 'Subang',
                'birth_date' => '2007-05-12',
                'religion' => 'Islam',

                'source_information' => 'Instagram',

                'registration_status' => 'biodata_lengkap',
                'document_status' => 'sebagian_upload',
                'payment_status' => 'belum_bayar',
                'selection_status' => 'belum_diseleksi',
                're_registration_status' => 'belum_daftar_ulang',
                'sync_status' => 'belum_siap',
            ]
        );

        $applicant->address()->updateOrCreate(
            ['applicant_id' => $applicant->id],
            [
                'address' => 'Jl. Industri Raya Blok A No. 12',
                'province_code' => '32',
                'province_name' => 'Jawa Barat',
                'regency_code' => '3216',
                'regency_name' => 'Kabupaten Bekasi',
                'district_code' => '321601',
                'district_name' => 'Cikarang Barat',
                'village_code' => '3216012001',
                'village_name' => 'Gandasari',
                'postal_code' => '17530',
                'rt' => '001',
                'rw' => '002',
            ]
        );

        $applicant->education()->updateOrCreate(
            ['applicant_id' => $applicant->id],
            [
                'school_npsn' => '20233677',
                'school_name' => 'SMKN 1 Subang',
                'school_type' => 'SMK',
                'school_status' => 'Negeri',
                'school_address' => 'Jl. Arief Rahman Hakim No.35, Subang, Jawa Barat',
                'major' => 'Rekayasa Perangkat Lunak',
                'graduation_year' => 2026,
                'average_score' => 86.50,
                'is_manual_school' => false,
                'manual_school_note' => null,
            ]
        );

        $applicant->parentData()->updateOrCreate(
            ['applicant_id' => $applicant->id],
            [
                'father_name' => 'Dadang Hidayat',
                'father_job' => 'Karyawan Swasta',
                'father_phone' => '081298765432',

                'mother_name' => 'Siti Nurjanah',
                'mother_job' => 'Ibu Rumah Tangga',
                'mother_phone' => '081298765433',

                'guardian_name' => null,
                'guardian_job' => null,
                'guardian_phone' => null,
                'guardian_relation' => null,

                'parent_income_range' => 'Rp2.000.000 - Rp5.000.000',
            ]
        );
    }
}