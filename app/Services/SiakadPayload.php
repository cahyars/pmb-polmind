<?php

namespace App\Services;

use App\Models\Applicant;

/**
 * Menyusun body request biodata mahasiswa untuk API SIAKAD Polmind
 * (format sama dengan body_request_mahasiswa.json).
 */
class SiakadPayload
{
    public static function build(Applicant $applicant): array
    {
        $applicant->loadMissing(['address', 'parentData']);

        $address = $applicant->address;
        $parent = $applicant->parentData;

        return [
            'record' => [
                'nama_mahasiswa' => $applicant->full_name,
                'jenis_kelamin' => $applicant->gender,
                'tempat_lahir' => $applicant->birth_place,
                'tanggal_lahir' => $applicant->birth_date?->format('Y-m-d'),
                'id_agama' => $applicant->religion_id,
                'nik' => $applicant->nik,
                'nisn' => self::text($applicant->nisn),
                'npwp' => self::text($applicant->npwp),
                'kewarganegaraan' => $applicant->citizenship ?: 'ID',
                'jalan' => self::text($address?->address),
                'dusun' => self::text($address?->hamlet),
                'rt' => self::number($address?->rt),
                'rw' => self::number($address?->rw),
                'kelurahan' => self::text($address?->village_name),
                'kode_pos' => self::number($address?->postal_code),
                'id_wilayah' => $address?->region_id,
                'id_jenis_tinggal' => $address?->residence_type_id,
                'id_alat_transportasi' => $applicant->transportation_id,
                'telepon' => self::text($applicant->home_phone),
                'handphone' => self::text($applicant->phone),
                'email' => $applicant->email,
                'penerima_kps' => $applicant->kps_recipient ? 1 : 0,
                'nomor_kps' => $applicant->kps_recipient ? self::text($applicant->kps_number) : '',
                'nik_ayah' => self::text($parent?->father_nik),
                'nama_ayah' => self::text($parent?->father_name),
                'tanggal_lahir_ayah' => $parent?->father_birth_date?->format('Y-m-d'),
                'id_pendidikan_ayah' => $parent?->father_education_id,
                'id_pekerjaan_ayah' => $parent?->father_job_id,
                'id_penghasilan_ayah' => $parent?->father_income_id,
                'nik_ibu' => self::text($parent?->mother_nik),
                'nama_ibu_kandung' => self::text($parent?->mother_name),
                'tanggal_lahir_ibu' => $parent?->mother_birth_date?->format('Y-m-d'),
                'id_pendidikan_ibu' => $parent?->mother_education_id,
                'id_pekerjaan_ibu' => $parent?->mother_job_id,
                'id_penghasilan_ibu' => $parent?->mother_income_id,
                'nama_wali' => self::text($parent?->guardian_name),
                'tanggal_lahir_wali' => $parent?->guardian_birth_date?->format('Y-m-d'),
                'id_pendidikan_wali' => $parent?->guardian_education_id,
                'id_pekerjaan_wali' => $parent?->guardian_job_id,
                'id_penghasilan_wali' => $parent?->guardian_income_id,
                'id_kebutuhan_khusus_mahasiswa' => (int) $applicant->special_need_id,
                'id_kebutuhan_khusus_ayah' => (int) ($parent?->father_special_need_id ?? 0),
                'id_kebutuhan_khusus_ibu' => (int) ($parent?->mother_special_need_id ?? 0),
            ],
        ];
    }

    /**
     * Label data wajib yang masih kosong sebelum camaba boleh disinkronkan.
     *
     * @return list<string>
     */
    public static function missingFields(Applicant $applicant): array
    {
        $applicant->loadMissing(['studyProgram', 'acceptedStudyProgram', 'classType', 'address', 'education', 'parentData']);

        $checks = [
            'Nama Lengkap' => $applicant->full_name,
            'NIK' => $applicant->nik,
            'Jenis Kelamin' => $applicant->gender,
            'Tempat Lahir' => $applicant->birth_place,
            'Tanggal Lahir' => $applicant->birth_date,
            'Agama' => $applicant->religion_id,
            'Kewarganegaraan' => $applicant->citizenship,
            'Nomor HP' => $applicant->phone,
            'Email' => $applicant->email,
            'Kelurahan' => $applicant->address?->village_name,
            'Kecamatan (Wilayah)' => $applicant->address?->region_id,
            'Nama Ibu Kandung' => $applicant->parentData?->mother_name,
            'Program Studi' => $applicant->enrolled_study_program?->code,
            'Jenis Kelas' => $applicant->classType?->code,
            'Asal Sekolah' => $applicant->education?->school_name,
            'Tahun Lulus' => $applicant->education?->graduation_year,
        ];

        if ($applicant->kps_recipient) {
            $checks['Nomor KPS'] = $applicant->kps_number;
        }

        return collect($checks)
            ->filter(fn ($value) => blank($value))
            ->keys()
            ->values()
            ->all();
    }

    private static function text(?string $value): string
    {
        return trim((string) $value);
    }

    private static function number(int|string|null $value): ?int
    {
        $value = trim((string) $value);

        return ctype_digit($value) ? (int) $value : null;
    }
}
