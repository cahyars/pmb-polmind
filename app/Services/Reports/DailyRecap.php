<?php

namespace App\Services\Reports;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\ClassType;
use App\Models\PmbYear;
use App\Models\StudyProgram;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Rekap harian pendaftaran untuk dikirim ke grup WhatsApp pimpinan, mengikuti template PMB.
 * Pendaftar resmi = semua yang sudah registrasi (sudah bayar formulir atau belum).
 * Hari rekap ditutup pukul 18.00 WIB: pertambahan hari H dihitung dari H-1 18.00 sampai
 * H 18.00, sehingga pendaftar malam hari masuk laporan sore keesokan harinya.
 */
class DailyRecap
{
    /**
     * Sekolah mitra yang lulusannya dihitung terpisah (poin 8).
     */
    public const PARTNER_SCHOOL = 'SMK Mitra Industri';

    public const CUTOFF_HOUR = 18;

    private const SCHOOL_TYPE_ORDER = ['SMA', 'SMK', 'MA', 'MAK', 'Paket C', 'Lainnya'];

    public function __construct(
        private ?PmbYear $pmbYear,
        private ?AdmissionWave $wave,
        private Carbon $date,
    ) {}

    public static function for(?PmbYear $pmbYear, ?AdmissionWave $wave, Carbon $date): self
    {
        return new self($pmbYear, $wave, $date->copy()->startOfDay());
    }

    public function text(): string
    {
        $official = $this->officialApplicants();
        $today = $official->filter(fn (Applicant $applicant) => $applicant->created_at->gte($this->windowStart()));
        $todayOffline = $today->where('registered_via', 'kampus')->count();
        $shortDate = $this->date->format('j/n/Y');
        $partner = $official->filter(fn (Applicant $applicant) => str_contains(
            mb_strtoupper((string) $applicant->education?->school_name),
            mb_strtoupper(self::PARTNER_SCHOOL)
        ));

        $lines = [
            'Selamat ' . self::greeting() . ' Bapak/Ibu, berikut merupakan hasil rekap Pendaftaran '
                . ($this->pmbYear?->name ?? 'PMB')
                . ($this->wave ? ' *' . $this->wave->name . '*' : '')
                . ' pada hari ' . $this->date->translatedFormat('l, j F Y'),
            '',
            '1. Total pendaftar resmi hingga tanggal ' . $shortDate . ' adalah sebanyak *' . $official->count() . ' pendaftar*.',
            '',
            '2. Pendaftar bertambah pada tanggal ' . $shortDate . ' sebanyak *' . $today->count() . ' pendaftar*, dengan rincian:',
            ...$this->bullets(['Daftar Online' => $today->count() - $todayOffline, 'Daftar Offline' => $todayOffline]),
            '',
            '3. Pilihan Program Studi :',
            ...$this->bullets($this->byStudyProgram($official)),
            '',
            '4. Total pendaftar berdasarkan asal sekolah:',
            ...$this->bullets($this->bySchool($official)),
            '',
            '5. Total pendaftar berdasarkan tahun lulus:',
            ...$this->bullets($this->byGraduationYear($official)),
            '',
            '6. Total pendaftar berdasarkan pilihan kelas:',
            ...$this->bullets($this->byClassType($official)),
            '',
            '7. Data pendaftar berdasarkan rekomendasi:',
            ...$this->bullets($official->countBy(fn ($applicant) => $applicant->source_information ?: 'Tidak diisi')->sortDesc()->all()),
            '',
            '8. Pendaftar dari lulusan ' . self::PARTNER_SCHOOL . ' total ada ' . $partner->count() . ' orang'
                . ($partner->isNotEmpty() ? ', dengan rincian tahun lulus:' : '.'),
            ...$this->bullets($partner->isEmpty() ? [] : $this->byGraduationYear($partner), emptyLine: false),
        ];

        return rtrim(implode("\n", $lines));
    }

    public static function greeting(?Carbon $now = null): string
    {
        $hour = ($now ?? now())->hour;

        return match (true) {
            $hour >= 4 && $hour < 11 => 'pagi',
            $hour >= 11 && $hour < 15 => 'siang',
            $hour >= 15 && $hour < 18 => 'sore',
            default => 'malam',
        };
    }

    /**
     * @return Collection<int, Applicant>
     */
    /**
     * Awal pertambahan hari H: H-1 pukul 18.00 WIB.
     */
    public function windowStart(): Carbon
    {
        return $this->date->copy()->subDay()->setTime(self::CUTOFF_HOUR, 0);
    }

    /**
     * Batas rekap hari H: H pukul 18.00 WIB (pendaftar setelahnya masuk rekap besok).
     */
    public function windowEnd(): Carbon
    {
        return $this->date->copy()->setTime(self::CUTOFF_HOUR, 0);
    }

    /**
     * @return Collection<int, Applicant>
     */
    private function officialApplicants(): Collection
    {
        return Applicant::query()
            ->with(['studyProgram', 'classType', 'education'])
            ->when($this->pmbYear, fn ($query) => $query->where('pmb_year_id', $this->pmbYear->id))
            ->when($this->wave, fn ($query) => $query->where('admission_wave_id', $this->wave->id))
            ->where('created_at', '<', $this->windowEnd())
            ->get();
    }

    private function byStudyProgram(Collection $applicants): array
    {
        $counts = $applicants->countBy('study_program_id');

        return StudyProgram::where('is_active', true)->orderBy('name')->get()
            ->mapWithKeys(fn (StudyProgram $program) => [$program->name => $counts[$program->id] ?? 0])
            ->all();
    }

    private function byClassType(Collection $applicants): array
    {
        $counts = $applicants->countBy('class_type_id');

        return ClassType::where('is_active', true)->orderBy('code')->get()
            ->mapWithKeys(fn (ClassType $classType) => [$classType->name => $counts[$classType->id] ?? 0])
            ->all();
    }

    /**
     * Jenis + status sekolah, contoh "SMA Negeri" dan "SMK Swasta", urut jenis lalu status.
     */
    private function bySchool(Collection $applicants): array
    {
        return $applicants
            ->groupBy(fn ($applicant) => trim(($applicant->education?->school_type ?? 'Belum diisi') . ' ' . ($applicant->education?->school_status ?? '')))
            ->map->count()
            ->sortBy(function ($count, string $label) {
                $type = collect(self::SCHOOL_TYPE_ORDER)->search(fn (string $type) => str_starts_with($label, $type . ' ') || $label === $type);

                return sprintf('%02d %s', $type === false ? 99 : $type, $label);
            })
            ->all();
    }

    /**
     * Tahun lulus terbaru di atas; yang belum mengisi di paling bawah.
     */
    private function byGraduationYear(Collection $applicants): array
    {
        $counts = $applicants->countBy(fn ($applicant) => (string) ($applicant->education?->graduation_year ?? ''));
        $unknown = $counts->pull('');

        return $counts->sortKeysDesc()->all() + ($unknown ? ['Belum diisi' => $unknown] : []);
    }

    /**
     * @return list<string>
     */
    private function bullets(array $counts, bool $emptyLine = true): array
    {
        if ($counts === []) {
            return $emptyLine ? ['- Belum ada data'] : [];
        }

        return collect($counts)->map(fn ($count, $label) => '- ' . $label . ': ' . $count)->values()->all();
    }
}
