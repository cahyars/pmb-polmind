<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionWave;
use App\Models\ClassType;
use App\Models\StudyProgram;
use App\Services\Reports\ApplicantReport;
use App\Services\Reports\ReportFilter;
use App\Services\Reports\ReRegistrationReport;
use App\Services\Reports\SelectionReport;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Submenu Laporan: slug URL => judul dan keterangan halaman.
     */
    public const SECTIONS = [
        'pendaftar' => ['label' => 'Laporan Pendaftar', 'description' => 'Asal sekolah, pilihan prodi, gender, tren bulanan, sumber informasi, dan latar pendidikan pendaftar.'],
        'seleksi' => ['label' => 'Laporan Seleksi', 'description' => 'Kesiapan dan hasil seleksi per prodi, jalur, dan gelombang.'],
        'daftar-ulang' => ['label' => 'Laporan Daftar Ulang', 'description' => 'Pembayaran daftar ulang, NIM, dan keterisian kuota prodi.'],
    ];

    public function index(Request $request)
    {
        return redirect()->route('admin.reports.show', ['section' => array_key_first(self::SECTIONS)] + $request->only(ReportFilter::KEYS));
    }

    public function show(Request $request, string $section)
    {
        $filter = ReportFilter::fromRequest($request);

        $report = match ($section) {
            'pendaftar' => ApplicantReport::build($filter),
            'seleksi' => SelectionReport::build($filter),
            'daftar-ulang' => ReRegistrationReport::build($filter),
        };

        return view('admin.reports.' . $section, [
            'report' => $report,
            'section' => $section,
            'sections' => self::SECTIONS,
            'filters' => $filter->values,
            'studyPrograms' => StudyProgram::orderBy('code')->get(),
            'classTypes' => ClassType::orderBy('code')->get(),
            'admissionWaves' => AdmissionWave::orderBy('start_date')->get(),
        ]);
    }

    public function exportApplicants(Request $request)
    {
        $filename = 'laporan-camaba-pmb-polmind-' . now()->format('Ymd-His') . '.csv';

        $applicants = ReportFilter::fromRequest($request)->applicants()
            ->with([
                'pmbYear',
                'admissionWave',
                'studyProgram',
                'classType',
                'education',
                'selection',
                'reRegistration',
            ])
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($applicants) {
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
                'Asal Sekolah',
                'Jurusan Asal',
                'Tahun Lulus',
                'Status Registrasi',
                'Status Berkas',
                'Status Pembayaran',
                'Status Seleksi',
                'Nilai Tes',
                'Nilai Interview',
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
                    $applicant->education?->school_name,
                    $applicant->education?->major,
                    $applicant->education?->graduation_year,
                    str_replace('_', ' ', $applicant->registration_status),
                    str_replace('_', ' ', $applicant->document_status),
                    str_replace('_', ' ', $applicant->payment_status),
                    str_replace('_', ' ', $applicant->selection_status),
                    $applicant->selection?->test_score,
                    $applicant->selection?->interview_score,
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
}
