<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SelectAdminPmbYear;
use App\Models\AdmissionWave;
use App\Models\ClassType;
use App\Models\InformationSource;
use App\Models\PmbYear;
use App\Models\SelectionComponent;
use App\Models\StudyProgram;
use App\Services\ActivityLogger;
use App\Services\Reports\ApplicantReport;
use App\Services\Reports\MitraFormatReport;
use App\Services\Reports\PaymentRecap;
use App\Services\Reports\PaymentRecapExport;
use App\Services\Reports\ReportFilter;
use App\Services\Reports\ReRegistrationReport;
use App\Services\Reports\SelectionReport;
use App\Services\Reports\SourceReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    /**
     * Submenu Laporan: slug URL => judul dan keterangan halaman.
     */
    public const SECTIONS = [
        'pendaftar' => ['label' => 'Laporan Pendaftar', 'description' => 'Asal sekolah, pilihan prodi, gender, tren bulanan, sumber informasi, dan latar pendidikan pendaftar.'],
        'sumber-informasi' => ['label' => 'Laporan Sumber Informasi', 'description' => 'Pendaftar dan konversinya per sumber informasi, tren bulanan, dan sebaran prodi untuk tim marketing.'],
        'seleksi' => ['label' => 'Laporan Seleksi', 'description' => 'Kesiapan dan hasil seleksi per prodi, jalur, dan gelombang.'],
        'daftar-ulang' => ['label' => 'Laporan Daftar Ulang', 'description' => 'Pembayaran daftar ulang, NIM, dan keterisian kuota prodi.'],
        'pembayaran' => ['label' => 'Rekap Pembayaran', 'description' => 'Pemasukan per camaba per minggu: formulir, daftar ulang, UKT, SPI, dan outstanding.'],
        'format-mitra' => ['label' => 'Report Format Mitra', 'description' => 'Rekap registrasi, pengisian formulir, dan grafiknya per hari seperti spreadsheet laporan sore, siap diunduh PNG.'],
    ];

    public function index(Request $request)
    {
        return redirect()->route('admin.reports.show', ['section' => array_key_first(self::SECTIONS)] + $request->only(ReportFilter::KEYS));
    }

    public function show(Request $request, string $section)
    {
        if ($section === 'format-mitra') {
            return $this->mitraFormat($request);
        }

        $filter = ReportFilter::fromRequest($request);

        $report = match ($section) {
            'pendaftar' => ApplicantReport::build($filter),
            'sumber-informasi' => SourceReport::build($filter),
            'seleksi' => SelectionReport::build($filter),
            'daftar-ulang' => ReRegistrationReport::build($filter),
            'pembayaran' => PaymentRecap::build($filter),
        };

        return view('admin.reports.' . $section, [
            'report' => $report,
            'section' => $section,
            'sections' => self::SECTIONS,
            'filters' => $filter->values,
            'studyPrograms' => StudyProgram::orderBy('code')->get(),
            'classTypes' => ClassType::orderBy('code')->get(),
            'admissionWaves' => AdmissionWave::orderBy('start_date')->get(),
            'informationSources' => InformationSource::ordered()->pluck('name'),
        ]);
    }

    /**
     * Report Format Mitra: per gelombang sampai hari H (default hari ini), minggu berjalan atau seluruh gelombang.
     */
    private function mitraFormat(Request $request)
    {
        $validated = $request->validate([
            'wave' => ['nullable', 'integer'],
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'range' => ['nullable', 'in:week,all'],
        ]);

        $pmbYear = PmbYear::find($request->session()->get(SelectAdminPmbYear::SESSION_KEY));
        $waves = AdmissionWave::orderBy('start_date')->orderBy('id')->get();
        $wave = $waves->firstWhere('id', (int) ($validated['wave'] ?? 0))
            ?? $waves->firstWhere('id', AdmissionWave::current($pmbYear)?->id)
            ?? $waves->last();

        return view('admin.reports.format-mitra', [
            'report' => $wave
                ? MitraFormatReport::build($pmbYear, $wave, Carbon::parse($validated['date'] ?? today()), ($validated['range'] ?? 'week') === 'all')
                : null,
            'section' => 'format-mitra',
            'sections' => self::SECTIONS,
            'filters' => [],
            'waves' => $waves,
        ]);
    }

    public function exportPayments(Request $request)
    {
        ActivityLogger::record('report.export', 'Mengunduh Rekap Pembayaran (Excel)', ['changes' => $this->exportFilters($request)]);

        $pmbYear = PmbYear::find($request->session()->get(SelectAdminPmbYear::SESSION_KEY));

        return PaymentRecapExport::download(
            PaymentRecap::build(ReportFilter::fromRequest($request)),
            'rekap-pembayaran-' . str($pmbYear?->name ?? 'pmb')->slug() . '-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function exportApplicants(Request $request)
    {
        ActivityLogger::record('report.export', 'Mengunduh data camaba (CSV)', ['changes' => $this->exportFilters($request)]);

        $filename = 'laporan-camaba-pmb-polmind-' . now()->format('Ymd-His') . '.csv';

        $applicants = ReportFilter::fromRequest($request)->applicants()
            ->with([
                'pmbYear',
                'admissionWave',
                'studyProgram',
                'classType',
                'education',
                'selection.scores',
                'acceptedStudyProgram',
                'reRegistration',
            ])
            ->latest()
            ->get();

        $components = SelectionComponent::orderBy('sort_order')->orderBy('name')->get();

        return response()->streamDownload(function () use ($applicants, $components) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 agar karakter Indonesia aman saat dibuka di Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'No',
                'No Pendaftaran',
                'Nama Lengkap',
                'Email',
                'No HP',
                'NIK',
                'NISN',
                'Jenis Kelamin',
                'Tempat Lahir',
                'Tanggal Lahir',
                'Tahun PMB',
                'Gelombang',
                'Program Studi',
                'Kelas',
                'Jalur Pendaftaran',
                'Cara Daftar',
                'Sumber Informasi',
                'Ukuran Jas Almamater',
                'Asal Sekolah',
                'Jurusan Asal',
                'Tahun Lulus',
                'Status Registrasi',
                'Status Berkas',
                'Status Pembayaran',
                'Status Seleksi',
                'Diterima di Prodi',
                ...$components->map(fn ($component) => 'Nilai ' . $component->name),
                'Nilai Akhir',
                'Status Daftar Ulang',
                'Status Sinkron',
                'NIM',
                'Tanggal Daftar',
            ]);

            foreach ($applicants as $index => $applicant) {
                fputcsv($handle, [
                    $index + 1,
                    $applicant->registration_number,
                    $applicant->full_name,
                    $applicant->email,
                    $applicant->phone,
                    $applicant->nik,
                    $applicant->nisn,
                    $applicant->gender_label,
                    $applicant->birth_place,
                    $applicant->birth_date?->format('d/m/Y'),
                    $applicant->pmbYear?->name,
                    $applicant->admissionWave?->name,
                    $applicant->studyProgram?->code . ' - ' . $applicant->studyProgram?->name,
                    $applicant->classType?->name,
                    $applicant->registration_path_label,
                    $applicant->registered_via === 'kampus' ? 'Di kampus' : 'Online',
                    $applicant->source_information,
                    $applicant->almamater_size,
                    $applicant->education?->school_name,
                    $applicant->education?->major,
                    $applicant->education?->graduation_year,
                    str_replace('_', ' ', $applicant->registration_status),
                    str_replace('_', ' ', $applicant->document_status),
                    str_replace('_', ' ', $applicant->payment_status),
                    str_replace('_', ' ', $applicant->selection_status),
                    $applicant->acceptedChoice() ? $applicant->enrolled_study_program?->code . ' (pilihan ' . $applicant->acceptedChoice() . ')' : null,
                    ...$components->map(fn ($component) => $applicant->selection?->scores->firstWhere('selection_component_id', $component->id)?->score),
                    $applicant->selection?->final_score,
                    str_replace('_', ' ', $applicant->re_registration_status),
                    str_replace('_', ' ', $applicant->sync_status),
                    $applicant->nim,
                    $applicant->created_at?->format('d/m/Y H:i'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Filter unduhan dicatat di rincian log, agar terlihat data mana yang diunduh.
     */
    private function exportFilters(Request $request): array
    {
        return collect($request->only(ReportFilter::KEYS))->filter(fn ($value) => filled($value))
            ->map(fn ($value, $key) => ['label' => [
                'start_date' => 'Dari tanggal', 'end_date' => 'Sampai tanggal', 'admission_wave' => 'Gelombang',
                'study_program' => 'Prodi', 'class_type' => 'Kelas', 'registration_path' => 'Jalur', 'source' => 'Sumber informasi',
            ][$key] ?? $key, 'old' => null, 'new' => (string) match ($key) {
                'admission_wave' => AdmissionWave::find($value)?->name ?? $value,
                'study_program' => StudyProgram::find($value)?->code ?? $value,
                'class_type' => ClassType::find($value)?->name ?? $value,
                default => $value,
            }])
            ->all();
    }
}
