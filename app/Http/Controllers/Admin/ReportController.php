<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\ClassType;
use App\Models\Payment;
use App\Models\StudyProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $studyPrograms = StudyProgram::where('is_active', true)
            ->orderBy('code')
            ->get();

        $classTypes = ClassType::where('is_active', true)
            ->orderBy('code')
            ->get();

        $baseApplicants = $this->filteredApplicants($request);

        $totalApplicants = (clone $baseApplicants)->count();
        $biodataComplete = (clone $baseApplicants)->where('registration_status', 'biodata_lengkap')->count();
        $documentValid = (clone $baseApplicants)->where('document_status', 'valid')->count();
        $paymentValid = (clone $baseApplicants)->where('payment_status', 'valid')->count();
        $acceptedApplicants = (clone $baseApplicants)->where('selection_status', 'diterima')->count();
        $reserveApplicants = (clone $baseApplicants)->where('selection_status', 'cadangan')->count();
        $rejectedApplicants = (clone $baseApplicants)->where('selection_status', 'ditolak')->count();
        $reRegistered = (clone $baseApplicants)->where('re_registration_status', 'daftar_ulang_valid')->count();
        $readySync = (clone $baseApplicants)->where('sync_status', 'siap_sinkron')->count();
        $processingSync = (clone $baseApplicants)->where('sync_status', 'proses_sinkron')->count();
        $synced = (clone $baseApplicants)->where('sync_status', 'sudah_sinkron')->count();
        $failedSync = (clone $baseApplicants)->where('sync_status', 'gagal_sinkron')->count();

        $targetApplicants = 180;

        $targetProgress = $targetApplicants > 0
            ? round(($totalApplicants / $targetApplicants) * 100)
            : 0;

        $funnel = collect([
            ['label' => 'Registrasi Awal', 'value' => $totalApplicants],
            ['label' => 'Biodata Lengkap', 'value' => $biodataComplete],
            ['label' => 'Berkas Valid', 'value' => $documentValid],
            ['label' => 'Pembayaran Valid', 'value' => $paymentValid],
            ['label' => 'Diterima', 'value' => $acceptedApplicants],
            ['label' => 'Daftar Ulang Valid', 'value' => $reRegistered],
            ['label' => 'Siap Sinkron SIAKAD', 'value' => $readySync],
            ['label' => 'Sudah Sinkron', 'value' => $synced],
        ])->map(function ($item) use ($totalApplicants) {
            $item['percent'] = $totalApplicants > 0
                ? round(($item['value'] / $totalApplicants) * 100)
                : 0;

            return $item;
        });

        $programReports = $studyPrograms->map(function ($program) use ($request) {
            $query = $this->filteredApplicants($request)
                ->where('study_program_id', $program->id);

            $registrants = (clone $query)->count();
            $accepted = (clone $query)->where('selection_status', 'diterima')->count();
            $reRegistered = (clone $query)->where('re_registration_status', 'daftar_ulang_valid')->count();
            $readySync = (clone $query)->where('sync_status', 'siap_sinkron')->count();
            $synced = (clone $query)->where('sync_status', 'sudah_sinkron')->count();

            $quota = max($program->quota, 1);

            return [
                'code' => $program->code,
                'name' => $program->name,
                'quota' => $program->quota,
                'registrants' => $registrants,
                'accepted' => $accepted,
                're_registered' => $reRegistered,
                'ready_sync' => $readySync,
                'synced' => $synced,
                'quota_percent' => round(($reRegistered / $quota) * 100),
            ];
        });

        $classReports = $classTypes->map(function ($classType) use ($request) {
            $query = $this->filteredApplicants($request)
                ->where('class_type_id', $classType->id);

            return [
                'code' => $classType->code,
                'name' => $classType->name,
                'registrants' => (clone $query)->count(),
                'accepted' => (clone $query)->where('selection_status', 'diterima')->count(),
                're_registered' => (clone $query)->where('re_registration_status', 'daftar_ulang_valid')->count(),
                'synced' => (clone $query)->where('sync_status', 'sudah_sinkron')->count(),
            ];
        });

        $registrationPathReports = collect([
            'umum' => 'Umum',
            'prestasi' => 'Prestasi',
            'undangan' => 'Undangan',
        ])->map(function ($label, $path) use ($request) {
            $query = $this->filteredApplicants($request)
                ->where('registration_path', $path);

            return [
                'key' => $path,
                'label' => $label,
                'registrants' => (clone $query)->count(),
                'accepted' => (clone $query)->where('selection_status', 'diterima')->count(),
                're_registered' => (clone $query)->where('re_registration_status', 'daftar_ulang_valid')->count(),
                'synced' => (clone $query)->where('sync_status', 'sudah_sinkron')->count(),
            ];
        })->values();

        $selectionReports = collect([
            'belum_diseleksi' => 'Belum Diseleksi',
            'diterima' => 'Diterima',
            'cadangan' => 'Cadangan',
            'ditolak' => 'Ditolak',
        ])->map(function ($label, $status) use ($request) {
            return [
                'key' => $status,
                'label' => $label,
                'total' => (clone $this->filteredApplicants($request))
                    ->where('selection_status', $status)
                    ->count(),
            ];
        })->values();

        $syncReports = collect([
            'belum_siap' => 'Belum Siap',
            'siap_sinkron' => 'Siap Sinkron',
            'proses_sinkron' => 'Proses Sinkron',
            'sudah_sinkron' => 'Sudah Sinkron',
            'gagal_sinkron' => 'Gagal Sinkron',
        ])->map(function ($label, $status) use ($request) {
            return [
                'key' => $status,
                'label' => $label,
                'total' => (clone $this->filteredApplicants($request))
                    ->where('sync_status', $status)
                    ->count(),
            ];
        })->values();

        $sourceReports = $this->filteredApplicants($request)
            ->select('source_information', DB::raw('COUNT(*) as total'))
            ->whereNotNull('source_information')
            ->groupBy('source_information')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $paymentQuery = Payment::query()
            ->whereHas('applicant', function ($query) use ($request) {
                $this->applyApplicantFilters($query, $request);
            });

        $waitingPayments = (clone $paymentQuery)->where('status', 'waiting_verification')->count();
        $validPayments = (clone $paymentQuery)->where('status', 'valid')->count();
        $rejectedPayments = (clone $paymentQuery)->where('status', 'rejected')->count();

        $totalPaid = (clone $paymentQuery)->where('status', 'valid')->sum('amount');

        $registrationPaid = (clone $paymentQuery)
            ->where('status', 'valid')
            ->whereHas('invoice', function ($query) {
                $query->where('type', 'registration');
            })
            ->sum('amount');

        $reRegistrationPaid = (clone $paymentQuery)
            ->where('status', 'valid')
            ->whereHas('invoice', function ($query) {
                $query->where('type', 're_registration');
            })
            ->sum('amount');

        $latestApplicants = $this->filteredApplicants($request)
            ->with(['studyProgram', 'classType'])
            ->latest()
            ->limit(5)
            ->get();

        $latestPayments = Payment::query()
            ->with(['applicant.studyProgram', 'invoice'])
            ->whereHas('applicant', function ($query) use ($request) {
                $this->applyApplicantFilters($query, $request);
            })
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.reports.index', compact(
            'studyPrograms',
            'classTypes',
            'totalApplicants',
            'biodataComplete',
            'documentValid',
            'paymentValid',
            'acceptedApplicants',
            'reserveApplicants',
            'rejectedApplicants',
            'reRegistered',
            'readySync',
            'processingSync',
            'synced',
            'failedSync',
            'targetApplicants',
            'targetProgress',
            'funnel',
            'programReports',
            'classReports',
            'registrationPathReports',
            'selectionReports',
            'syncReports',
            'sourceReports',
            'waitingPayments',
            'validPayments',
            'rejectedPayments',
            'totalPaid',
            'registrationPaid',
            'reRegistrationPaid',
            'latestApplicants',
            'latestPayments'
        ));
    }

    public function exportApplicants(Request $request)
    {
        $filename = 'laporan-camaba-pmb-polmind-' . now()->format('Ymd-His') . '.csv';

        $applicants = $this->filteredApplicants($request)
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
                    $applicant->gender,
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

    private function filteredApplicants(Request $request)
    {
        $query = Applicant::query();

        $this->applyApplicantFilters($query, $request);

        return $query;
    }

    private function applyApplicantFilters($query, Request $request): void
    {
        $query
            ->when($request->filled('start_date'), function ($query) use ($request) {
                $query->whereDate('created_at', '>=', $request->start_date);
            })
            ->when($request->filled('end_date'), function ($query) use ($request) {
                $query->whereDate('created_at', '<=', $request->end_date);
            })
            ->when($request->filled('study_program'), function ($query) use ($request) {
                $query->where('study_program_id', $request->study_program);
            })
            ->when($request->filled('class_type'), function ($query) use ($request) {
                $query->where('class_type_id', $request->class_type);
            })
            ->when($request->filled('registration_path'), function ($query) use ($request) {
                $query->where('registration_path', $request->registration_path);
            })
            ->when($request->filled('selection_status'), function ($query) use ($request) {
                $query->where('selection_status', $request->selection_status);
            })
            ->when($request->filled('re_registration_status'), function ($query) use ($request) {
                $query->where('re_registration_status', $request->re_registration_status);
            })
            ->when($request->filled('sync_status'), function ($query) use ($request) {
                $query->where('sync_status', $request->sync_status);
            });
    }
}