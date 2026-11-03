<?php

namespace App\Services;

use App\Http\Controllers\Admin\LoginAsController;
use App\Models\ActivityLog;
use App\Models\Applicant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pencatat Log Aktivitas: menentukan pelaku dari request yang sedang berjalan lalu menyimpan satu baris log.
 * Pelaku di rute admin = akun admin/operator; di portal camaba = camaba (beserta admin bila sedang Login As).
 */
class ActivityLogger
{
    /**
     * Label kolom yang tampil di rincian perubahan; kolom lain memakai judul dari nama kolomnya.
     */
    public const FIELD_LABELS = [
        'full_name' => 'Nama lengkap', 'nik' => 'NIK', 'nisn' => 'NISN', 'npwp' => 'NPWP', 'gender' => 'Jenis kelamin',
        'birth_place' => 'Tempat lahir', 'birth_date' => 'Tanggal lahir', 'religion_id' => 'Agama', 'citizenship' => 'Kewarganegaraan',
        'special_need_id' => 'Kebutuhan khusus', 'kps_recipient' => 'Penerima KPS', 'kps_number' => 'Nomor KPS', 'email' => 'Email',
        'phone' => 'No. HP', 'home_phone' => 'Telepon rumah', 'transportation_id' => 'Alat transportasi', 'almamater_size' => 'Ukuran jas',
        'password' => 'Password/PIN', 'registration_number' => 'Nomor pendaftaran', 'registration_status' => 'Status registrasi',
        'document_status' => 'Status berkas', 'payment_status' => 'Status pembayaran', 'selection_status' => 'Status seleksi',
        're_registration_status' => 'Status daftar ulang', 'sync_status' => 'Status sinkron SIAKAD', 'nim' => 'NIM',
        'study_program_id' => 'Prodi pilihan 1', 'second_study_program_id' => 'Prodi pilihan 2', 'accepted_study_program_id' => 'Prodi diterima',
        'class_type_id' => 'Kelas', 'selection_sequence' => 'Nomor urut peserta', 'selection_group_id' => 'Kelompok tes', 'registration_path' => 'Jalur', 'admission_wave_id' => 'Gelombang', 'source_information' => 'Sumber informasi',
        'address' => 'Jalan', 'hamlet' => 'Dusun', 'rt' => 'RT', 'rw' => 'RW', 'village_name' => 'Desa/Kelurahan', 'district_name' => 'Kecamatan',
        'regency_name' => 'Kab/Kota', 'province_name' => 'Provinsi', 'postal_code' => 'Kode pos', 'residence_type_id' => 'Jenis tinggal',
        'school_name' => 'Asal sekolah', 'school_npsn' => 'NPSN', 'school_type' => 'Jenis sekolah', 'school_status' => 'Status sekolah',
        'major' => 'Jurusan', 'graduation_year' => 'Tahun lulus', 'school_address' => 'Alamat sekolah',
        'father_name' => 'Nama ayah', 'mother_name' => 'Nama ibu', 'guardian_name' => 'Nama wali',
        'status' => 'Status', 'amount' => 'Nominal', 'total_amount' => 'Total', 'admin_note' => 'Catatan admin', 'note' => 'Catatan',
        'file_name' => 'Nama file', 'transfer_date' => 'Tanggal transfer', 'sender_name' => 'Nama pengirim', 'sender_bank' => 'Bank pengirim',
        'verified_by_name' => 'Diverifikasi oleh', 'waiver_reason' => 'Dasar pembebasan', 'score' => 'Nilai', 'final_score' => 'Nilai akhir',
        'name' => 'Nama', 'code' => 'Kode', 'is_active' => 'Aktif', 'role' => 'Peran', 'start_date' => 'Tanggal mulai', 'end_date' => 'Tanggal selesai',
        'due_date' => 'Jatuh tempo', 'value' => 'Nilai', 'description' => 'Keterangan',
    ];

    /**
     * Kolom yang isinya tidak pernah disimpan ke log, cukup ditandai "(diubah)".
     */
    public const SECRET_FIELDS = ['password', 'remember_token', 'token', 'api_token', 'token_hash'];

    private static ?string $batch = null;

    /**
     * @param  array<string, array{label: string, old: ?string, new: ?string}>  $changes
     */
    public static function record(string $action, string $description, array $context = []): ?ActivityLog
    {
        try {
            $actor = $context['actor'] ?? self::actor();

            return ActivityLog::create([
                'batch' => self::batch(),
                'actor_type' => $actor['type'],
                'actor_id' => $actor['id'],
                'actor_name' => Str::limit($actor['name'], 250, ''),
                'actor_role' => $actor['role'],
                'impersonator_name' => $actor['impersonator'] ?? null,
                'action' => $action,
                'description' => Str::limit($description, 250, ''),
                'subject_type' => $context['subject_type'] ?? null,
                'subject_id' => $context['subject_id'] ?? null,
                'subject_label' => isset($context['subject_label']) ? Str::limit($context['subject_label'], 250, '') : null,
                'applicant_id' => $context['applicant_id'] ?? null,
                'changes' => ($context['changes'] ?? []) ?: null,
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
                'user_agent' => app()->runningInConsole() ? null : Str::limit((string) request()->userAgent(), 250, ''),
            ]);
        } catch (Throwable $error) {
            // Kegagalan mencatat log tidak boleh menggagalkan pekerjaan admin/camaba.
            report($error);

            return null;
        }
    }

    /**
     * @return array{type: string, id: ?int, name: string, role: ?string, impersonator?: ?string}
     */
    public static function actor(): array
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return ['type' => 'system', 'id' => null, 'name' => 'Sistem (perintah server)', 'role' => null];
        }

        $route = (string) request()->route()?->getName();
        $user = Auth::guard('web')->user();
        $applicant = Auth::guard('applicant')->user();

        if ($user && str_starts_with($route, 'admin.')) {
            return self::userActor($user);
        }

        if ($applicant && ! str_starts_with($route, 'admin.')) {
            return [
                'type' => 'applicant', 'id' => $applicant->id, 'name' => $applicant->full_name, 'role' => 'Camaba',
                'impersonator' => LoginAsController::impersonator()['admin_name'] ?? null,
            ];
        }

        if ($route === 'register.store') {
            return ['type' => 'applicant', 'id' => null, 'name' => 'Pendaftar baru (registrasi online)', 'role' => 'Camaba'];
        }

        if (str_starts_with($route, 'api.') || request()->is('api/*')) {
            return ['type' => 'system', 'id' => null, 'name' => 'API SIAKAD', 'role' => null];
        }

        return $user ? self::userActor($user) : ['type' => 'system', 'id' => null, 'name' => 'Sistem', 'role' => null];
    }

    public static function userActor(User $user): array
    {
        return ['type' => 'user', 'id' => $user->id, 'name' => $user->name, 'role' => $user->role_label];
    }

    public static function applicantActor(Applicant $applicant): array
    {
        return ['type' => 'applicant', 'id' => $applicant->id, 'name' => $applicant->full_name, 'role' => 'Camaba'];
    }

    public static function applicantLabel(?int $applicantId): ?string
    {
        static $labels = [];

        if (! $applicantId) {
            return null;
        }

        return $labels[$applicantId] ??= (function () use ($applicantId) {
            $applicant = Applicant::withoutGlobalScopes()->find($applicantId, ['registration_number', 'full_name']);

            return $applicant ? $applicant->registration_number . ' · ' . $applicant->full_name : null;
        })();
    }

    public static function label(string $field): string
    {
        return self::FIELD_LABELS[$field] ?? Str::of($field)->replaceLast('_id', '')->replace('_', ' ')->ucfirst()->toString();
    }

    public static function format(mixed $value): ?string
    {
        return match (true) {
            $value === null || $value === '' => null,
            is_bool($value) => $value ? 'Ya' : 'Tidak',
            $value instanceof Carbon => $value->format($value->format('H:i:s') === '00:00:00' ? 'd/m/Y' : 'd/m/Y H:i'),
            is_array($value) => Str::limit(json_encode($value, JSON_UNESCAPED_UNICODE), 200),
            // Kolom tanggal tanpa cast tersimpan sebagai teks "2026-11-01".
            is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/', $value) => self::format(Carbon::parse($value)),
            default => Str::limit((string) $value, 200),
        };
    }

    /**
     * Satu batch per request, agar aksi berantai (contoh: validasi pembayaran -> invoice -> status camaba) bisa dikelompokkan.
     */
    private static function batch(): string
    {
        if (app()->runningInConsole()) {
            return self::$batch ??= (string) Str::uuid();
        }

        $request = request();

        if (! $request->attributes->has('activity_batch')) {
            $request->attributes->set('activity_batch', (string) Str::uuid());
        }

        return $request->attributes->get('activity_batch');
    }
}
