<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\DocumentType;
use Illuminate\Support\Collection;

/**
 * Status tiap tahapan pendaftaran camaba: kelengkapan biodata per bagian,
 * berkas, pembayaran formulir, dan tahapan yang harus dilanjutkan berikutnya.
 */
class ApplicantJourney
{
    /**
     * Bagian wizard biodata beserta kolom wajibnya.
     */
    public const BIODATA_SECTIONS = [
        'pribadi' => [
            'label' => 'Data Diri',
            'description' => 'Identitas sesuai KTP dan kontak.',
        ],
        'prodi' => [
            'label' => 'Program Studi',
            'description' => 'Pilihan prodi, kelas, dan jalur.',
        ],
        'alamat' => [
            'label' => 'Alamat',
            'description' => 'Alamat domisili sesuai KK.',
        ],
        'sekolah' => [
            'label' => 'Sekolah Asal',
            'description' => 'Pendidikan terakhir.',
        ],
        'orangtua' => [
            'label' => 'Orang Tua / Wali',
            'description' => 'Data ayah, ibu kandung, dan wali.',
        ],
    ];

    private ?Collection $requiredDocumentTypes = null;

    public function __construct(private Applicant $applicant)
    {
        $this->applicant->loadMissing(['address', 'education', 'parentData', 'documents']);
    }

    public static function for(Applicant $applicant): self
    {
        return new self($applicant);
    }

    public function registrationPaid(): bool
    {
        return $this->applicant->payment_status === 'valid';
    }

    public function paymentWaiting(): bool
    {
        return $this->applicant->payment_status === 'menunggu_verifikasi';
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function sectionValues(): array
    {
        $applicant = $this->applicant;

        return [
            'pribadi' => [
                $applicant->full_name,
                $applicant->nik,
                $applicant->gender,
                $applicant->religion_id,
                $applicant->birth_place,
                $applicant->birth_date,
                $applicant->citizenship,
                $applicant->phone,
                $applicant->email,
                $applicant->almamater_size,
            ],
            'prodi' => [
                $applicant->study_program_id,
                $applicant->class_type_id,
                RegistrationNumber::isFinal($applicant->registration_number) ?: null,
                $applicant->registration_path,
            ],
            'alamat' => [
                $applicant->address?->address,
                $applicant->address?->village_name,
                $applicant->address?->region_id,
            ],
            'sekolah' => [
                $applicant->education?->school_name,
                $applicant->education?->school_type,
                $applicant->education?->major,
                $applicant->education?->graduation_year,
            ],
            'orangtua' => [
                $applicant->parentData?->mother_name,
            ],
        ];
    }

    public function sectionComplete(string $section): bool
    {
        return collect($this->sectionValues()[$section] ?? [null])
            ->every(fn ($value) => filled($value));
    }

    /**
     * @return array<string, array{label: string, description: string, complete: bool}>
     */
    public function biodataSections(): array
    {
        return collect(self::BIODATA_SECTIONS)
            ->map(fn (array $section, string $key) => $section + ['complete' => $this->sectionComplete($key)])
            ->all();
    }

    public function biodataComplete(): bool
    {
        return collect(array_keys(self::BIODATA_SECTIONS))
            ->every(fn (string $section) => $this->sectionComplete($section));
    }

    public function firstIncompleteSection(): string
    {
        return collect(array_keys(self::BIODATA_SECTIONS))
            ->first(fn (string $section) => ! $this->sectionComplete($section)) ?? 'ringkasan';
    }

    public function requiredDocumentTypes(): Collection
    {
        return $this->requiredDocumentTypes ??= DocumentType::where('is_active', true)
            ->where('is_required', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Semua berkas wajib sudah diunggah dan tidak ada yang ditolak.
     */
    public function documentsComplete(): bool
    {
        $documents = $this->applicant->documents->keyBy('document_type_id');

        return $this->requiredDocumentTypes()->every(function (DocumentType $type) use ($documents) {
            $document = $documents->get($type->id);

            return $document?->file_path && in_array($document->status, ['menunggu_verifikasi', 'diterima'], true);
        });
    }

    public function uploadedRequiredCount(): int
    {
        $documents = $this->applicant->documents->keyBy('document_type_id');

        return $this->requiredDocumentTypes()
            ->filter(fn (DocumentType $type) => filled($documents->get($type->id)?->file_path))
            ->count();
    }

    /**
     * Tahapan timeline dashboard.
     * state: done | current | waiting | locked
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function steps(): Collection
    {
        $applicant = $this->applicant;
        $paid = $this->registrationPaid();
        $biodataComplete = $this->biodataComplete();
        $documentsComplete = $this->documentsComplete();
        $selectionDecided = in_array($applicant->selection_status, ['diterima', 'cadangan', 'ditolak'], true);
        $accepted = $applicant->selection_status === 'diterima';
        $requiredCount = $this->requiredDocumentTypes()->count();

        $steps = [
            [
                'key' => 'akun',
                'label' => 'Buat Akun',
                'description' => 'Akun pendaftaran berhasil dibuat.',
                'state' => 'done',
                'url' => null,
                'action' => null,
            ],
            [
                'key' => 'pembayaran',
                'label' => 'Bayar Formulir',
                'description' => match (true) {
                    $paid && $applicant->registrationInvoice?->isWaived() => 'Biaya formulir dibebaskan, tidak perlu membayar.',
                    $paid => 'Pembayaran formulir sudah diverifikasi.',
                    $this->paymentWaiting() => 'Bukti bayar terkirim, menunggu verifikasi admin (maks. 1x24 jam kerja).',
                    $applicant->payment_status === 'ditolak' => 'Bukti bayar ditolak. Silakan unggah ulang bukti yang benar.',
                    default => 'Bayar biaya formulir lalu unggah bukti transfer.',
                },
                'state' => $paid ? 'done' : ($this->paymentWaiting() ? 'waiting' : 'current'),
                'url' => route('camaba.payments.index'),
                'action' => $this->paymentWaiting() ? 'Lihat Pembayaran' : 'Bayar Formulir',
            ],
            [
                'key' => 'biodata',
                'label' => 'Lengkapi Biodata',
                'description' => $biodataComplete
                    ? 'Semua data wajib sudah terisi.'
                    : collect($this->biodataSections())->where('complete', true)->count() . ' dari ' . count(self::BIODATA_SECTIONS) . ' bagian biodata terisi.',
                'state' => ! $paid ? 'locked' : ($biodataComplete ? 'done' : 'current'),
                'url' => route('camaba.biodata.edit', ['step' => $this->firstIncompleteSection()]),
                'action' => 'Isi Biodata',
            ],
            [
                'key' => 'berkas',
                'label' => 'Upload Berkas',
                'description' => $documentsComplete
                    ? ($applicant->document_status === 'valid' ? 'Semua berkas wajib sudah diverifikasi.' : 'Semua berkas wajib terunggah, menunggu verifikasi admin.')
                    : $this->uploadedRequiredCount() . ' dari ' . $requiredCount . ' berkas wajib terunggah.',
                'state' => ! $paid ? 'locked' : ($documentsComplete ? 'done' : 'current'),
                'url' => route('camaba.documents.index'),
                'action' => 'Upload Berkas',
            ],
            [
                'key' => 'seleksi',
                'label' => 'Seleksi & Pengumuman',
                'description' => $selectionDecided
                    ? 'Hasil seleksi sudah diumumkan.'
                    : 'Ikuti ujian seleksi sesuai jadwal gelombang, lalu pantau pengumuman.',
                'state' => ! ($paid && $biodataComplete && $documentsComplete) ? 'locked' : ($selectionDecided ? 'done' : 'waiting'),
                'url' => route($selectionDecided ? 'camaba.pengumuman' : 'camaba.status-seleksi'),
                'action' => $selectionDecided ? 'Lihat Pengumuman' : 'Lihat Status Seleksi',
            ],
            [
                'key' => 'daftar_ulang',
                'label' => 'Daftar Ulang',
                'description' => match (true) {
                    $applicant->re_registration_status === 'daftar_ulang_valid' => 'Daftar ulang sudah valid.',
                    $accepted => 'Selamat, Anda diterima! Selesaikan daftar ulang.',
                    default => 'Dibuka setelah dinyatakan diterima.',
                },
                'state' => ! $accepted ? 'locked' : ($applicant->re_registration_status === 'daftar_ulang_valid' ? 'done' : 'current'),
                'url' => route('camaba.re-registration.index'),
                'action' => 'Daftar Ulang',
            ],
            [
                'key' => 'nim',
                'label' => 'Terdaftar sebagai Mahasiswa',
                'description' => $applicant->nim
                    ? 'NIM Anda: ' . $applicant->nim
                    : 'Data dikirim ke SIAKAD dan NIM diterbitkan.',
                'state' => $applicant->sync_status === 'sudah_sinkron'
                    ? 'done'
                    : ($applicant->re_registration_status === 'daftar_ulang_valid' ? 'waiting' : 'locked'),
                'url' => null,
                'action' => null,
            ],
        ];

        return collect($steps);
    }

    /**
     * Tahapan pertama yang belum selesai (tujuan tombol "Lanjutkan").
     */
    public function nextStep(): ?array
    {
        return $this->steps()->first(fn (array $step) => in_array($step['state'], ['current', 'waiting'], true));
    }

    public function progressPercent(): int
    {
        $steps = $this->steps();

        return (int) round($steps->where('state', 'done')->count() / $steps->count() * 100);
    }
}
