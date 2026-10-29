<?php

namespace App\Services;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\RefOption;
use App\Models\RefRegion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Aturan dan penyimpanan biodata camaba, dipakai bersama oleh wizard biodata camaba
 * dan form pendaftar di kampus (admin), supaya isian keduanya selalu sama.
 */
class ApplicantBiodata
{
    public const ALMAMATER_OTHER = 'Lainnya';

    /**
     * Rapikan isian sebelum divalidasi: nama kapital, email kecil, NIK tanpa spasi,
     * dan ukuran jas "Lainnya" diganti ukuran yang ditulis.
     */
    public static function prepare(Request $request): void
    {
        $size = (string) $request->input('almamater_size');

        $request->merge([
            'full_name' => mb_strtoupper(trim((string) $request->input('full_name'))),
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            'nik' => preg_replace('/\s+/', '', (string) $request->input('nik')),
            'almamater_size' => $size === self::ALMAMATER_OTHER
                ? mb_strtoupper(trim((string) $request->input('almamater_size_other')))
                : $size,
        ]);
    }

    public static function personalRules(?int $admissionWaveId, ?int $ignoreId = null): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'nik' => Applicant::nikRules($admissionWaveId, $ignoreId),
            'nisn' => ['nullable', 'digits:10'],
            'npwp' => ['nullable', 'regex:/^[0-9]{15,16}$/'],
            'gender' => ['required', 'in:L,P'],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:today'],
            'religion_id' => ['required', self::refRule(RefOption::AGAMA)],
            'citizenship' => ['required', self::refRule(RefOption::KEWARGANEGARAAN)],
            'special_need_id' => ['required', self::refRule(RefOption::KEBUTUHAN_KHUSUS)],
            'kps_recipient' => ['required', 'boolean'],
            'kps_number' => ['nullable', 'required_if:kps_recipient,1', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', Rule::unique('applicants', 'email')->ignore($ignoreId)],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{9,15}$/'],
            'home_phone' => ['nullable', 'string', 'regex:/^[0-9]{6,15}$/'],
            'transportation_id' => ['nullable', self::refRule(RefOption::ALAT_TRANSPORTASI)],
            'almamater_size' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9 \/-]+$/'],
        ];
    }

    public static function personalMessages(?AdmissionWave $wave): array
    {
        return [
            'phone.regex' => 'Nomor HP hanya boleh berisi angka (9-15 digit).',
            'home_phone.regex' => 'Telepon rumah hanya boleh berisi angka.',
            'npwp.regex' => 'NPWP harus 15 atau 16 digit angka.',
            'kps_number.required_if' => 'Nomor KPS wajib diisi untuk penerima KPS.',
            'almamater_size.required' => 'Pilih ukuran jas almamater, atau tulis ukurannya bila memilih Lainnya.',
            'almamater_size.regex' => 'Ukuran jas almamater hanya boleh berisi huruf dan angka, contoh XXXL.',
        ] + Applicant::nikMessages($wave);
    }

    public static function personalAttributes(array $validated): array
    {
        return [
            'full_name' => $validated['full_name'],
            'nik' => $validated['nik'],
            'nisn' => self::blankToNull($validated['nisn'] ?? null),
            'npwp' => self::blankToNull($validated['npwp'] ?? null),
            'gender' => $validated['gender'],
            'birth_place' => $validated['birth_place'],
            'birth_date' => $validated['birth_date'],
            'religion_id' => $validated['religion_id'],
            'citizenship' => $validated['citizenship'],
            'special_need_id' => $validated['special_need_id'],
            'kps_recipient' => (bool) $validated['kps_recipient'],
            'kps_number' => $validated['kps_recipient'] ? self::blankToNull($validated['kps_number'] ?? null) : null,
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'home_phone' => self::blankToNull($validated['home_phone'] ?? null),
            'transportation_id' => self::blankToNull($validated['transportation_id'] ?? null),
            'almamater_size' => $validated['almamater_size'],
        ];
    }

    /**
     * Pilihan ukuran jas untuk form: [pilihan dropdown, ukuran yang ditulis bila "Lainnya"].
     *
     * @return array{0: string, 1: string}
     */
    public static function almamaterChoice(?string $stored): array
    {
        if (blank($stored) || in_array($stored, Applicant::ALMAMATER_SIZES, true)) {
            return [(string) $stored, ''];
        }

        return [self::ALMAMATER_OTHER, $stored];
    }

    /**
     * Tanpa $required (form admin), alamat boleh dikosongkan seluruhnya.
     */
    public static function addressRules(bool $required = true): array
    {
        $needed = $required ? 'required' : 'required_with:address';

        return [
            'address' => [$required ? 'required' : 'nullable', 'string', 'max:500'],
            'hamlet' => ['nullable', 'string', 'max:100'],
            'rt' => ['nullable', 'digits_between:1,3'],
            'rw' => ['nullable', 'digits_between:1,3'],
            'village_name' => [$needed, 'nullable', 'string', 'max:100'],
            'region_id' => [$needed, 'nullable', Rule::exists('ref_regions', 'id')->where('level', RefRegion::LEVEL_DISTRICT)],
            'postal_code' => ['nullable', 'digits:5'],
            'residence_type_id' => ['nullable', self::refRule(RefOption::JENIS_TINGGAL)],
        ];
    }

    public static function addressMessages(): array
    {
        return [
            'region_id.required' => 'Pilih provinsi, kabupaten/kota, dan kecamatan.',
            'region_id.required_with' => 'Pilih provinsi, kabupaten/kota, dan kecamatan alamat.',
            'village_name.required_with' => 'Desa/kelurahan wajib diisi bila alamat diisi.',
        ];
    }

    public static function saveAddress(Applicant $applicant, array $validated): void
    {
        $district = RefRegion::with('parent.parent')->findOrFail($validated['region_id']);

        $applicant->address()->updateOrCreate(
            ['applicant_id' => $applicant->id],
            [
                'address' => $validated['address'],
                'hamlet' => self::blankToNull($validated['hamlet'] ?? null),
                'rt' => self::padNumber($validated['rt'] ?? null),
                'rw' => self::padNumber($validated['rw'] ?? null),
                'village_code' => null,
                'village_name' => $validated['village_name'],
                'region_id' => $district->id,
                'district_code' => $district->id,
                'district_name' => $district->name,
                'regency_code' => $district->parent?->id,
                'regency_name' => $district->parent?->name,
                'province_code' => $district->parent?->parent?->id,
                'province_name' => $district->parent?->parent?->name,
                'postal_code' => self::blankToNull($validated['postal_code'] ?? null),
                'residence_type_id' => self::blankToNull($validated['residence_type_id'] ?? null),
            ]
        );
    }

    public static function parentRules(bool $motherRequired = true): array
    {
        $person = fn (string $prefix, bool $nameRequired = false) => [
            "{$prefix}_name" => [$nameRequired ? 'required' : 'nullable', 'string', 'max:255'],
            "{$prefix}_nik" => ['nullable', 'digits:16'],
            "{$prefix}_birth_date" => ['nullable', 'date', 'before:today'],
            "{$prefix}_education_id" => ['nullable', self::refRule(RefOption::PENDIDIKAN)],
            "{$prefix}_job_id" => ['nullable', self::refRule(RefOption::PEKERJAAN)],
            "{$prefix}_income_id" => ['nullable', self::refRule(RefOption::PENGHASILAN)],
            "{$prefix}_phone" => ['nullable', 'string', 'regex:/^\+?[0-9]{9,15}$/'],
        ];

        return $person('father')
            + $person('mother', $motherRequired)
            + $person('guardian')
            + [
                'father_special_need_id' => ['required', self::refRule(RefOption::KEBUTUHAN_KHUSUS)],
                'mother_special_need_id' => ['required', self::refRule(RefOption::KEBUTUHAN_KHUSUS)],
                'guardian_relation' => ['nullable', 'string', 'max:100'],
            ];
    }

    public static function parentMessages(): array
    {
        return ['*_phone.regex' => 'Nomor HP hanya boleh berisi angka (9-15 digit).'];
    }

    public static function parentAttributes(array $validated): array
    {
        $data = collect($validated)
            ->only(array_keys(self::parentRules()))
            ->map(fn ($value) => self::blankToNull($value))
            ->all();

        $data['mother_name'] = filled($validated['mother_name'] ?? null) ? mb_strtoupper($validated['mother_name']) : null;
        $data['father_special_need_id'] = $validated['father_special_need_id'];
        $data['mother_special_need_id'] = $validated['mother_special_need_id'];

        return $data;
    }

    /**
     * Pilihan referensi Feeder untuk dropdown biodata.
     */
    public static function references()
    {
        return collect([
            RefOption::AGAMA,
            RefOption::PENDIDIKAN,
            RefOption::PEKERJAAN,
            RefOption::PENGHASILAN,
            RefOption::JENIS_TINGGAL,
            RefOption::ALAT_TRANSPORTASI,
            RefOption::KEBUTUHAN_KHUSUS,
            RefOption::KEWARGANEGARAAN,
        ])->mapWithKeys(fn (string $group) => [$group => RefOption::optionsFor($group)]);
    }

    public static function refRule(string $group)
    {
        return Rule::in(RefOption::codesFor($group));
    }

    public static function blankToNull($value)
    {
        return filled($value) ? $value : null;
    }

    private static function padNumber(?string $value): ?string
    {
        return filled($value) ? str_pad(ltrim($value, '0') ?: '0', 3, '0', STR_PAD_LEFT) : null;
    }
}
