<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\IntegrationLog;
use App\Models\StudyProgram;
use App\Services\SiakadPayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * SIAKAD menarik mahasiswa baru (sudah daftar ulang valid dan sudah ber-NIM),
 * lalu mengonfirmasi hasil penyimpanannya per mahasiswa.
 */
class SiakadStudentController extends Controller
{
    /**
     * Ditampilkan secara default: menunggu ditarik, atau sedang diproses admin.
     */
    public const PULLABLE_STATUSES = ['siap_sinkron', 'proses_sinkron'];

    private const VISIBLE_STATUSES = ['siap_sinkron', 'proses_sinkron', 'sudah_sinkron', 'gagal_sinkron'];

    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(self::VISIBLE_STATUSES)],
            'tahun' => ['nullable', 'integer'],
            'prodi' => ['nullable', 'string', 'max:20'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $students = $this->students()
            ->when(
                filled($validated['status'] ?? null),
                fn ($query) => $query->where('sync_status', $validated['status']),
                fn ($query) => $query->whereIn('sync_status', self::PULLABLE_STATUSES)
            )
            ->when(filled($validated['tahun'] ?? null), fn ($query) => $query->whereHas('pmbYear', fn ($q) => $q->where('year', $validated['tahun'])))
            ->when(filled($validated['prodi'] ?? null), fn ($query) => $query->whereEnrolledProgram(
                StudyProgram::where('code', $validated['prodi'])->value('id') ?? 0
            ))
            ->orderBy('nim')
            ->paginate($validated['per_page'] ?? 50)
            ->withQueryString();

        return response()->json([
            'data' => $students->getCollection()->map(fn ($applicant) => SiakadPayload::student($applicant))->values(),
            'meta' => [
                'page' => $students->currentPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
                'last_page' => $students->lastPage(),
            ],
        ]);
    }

    public function show(Applicant $applicant)
    {
        if (! $this->isVisible($applicant)) {
            return $this->notFound();
        }

        return response()->json(['data' => SiakadPayload::student($applicant)]);
    }

    /**
     * Body: status=berhasil + nim (wajib sama dengan NIM di PMB) + id_mahasiswa (opsional),
     * atau status=gagal + pesan.
     */
    public function confirm(Request $request, Applicant $applicant)
    {
        if (! $this->isVisible($applicant)) {
            return $this->notFound();
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['berhasil', 'gagal'])],
            'nim' => ['required_if:status,berhasil', 'nullable', 'string', 'max:30'],
            'id_mahasiswa' => ['nullable', 'string', 'max:100'],
            'pesan' => ['required_if:status,gagal', 'nullable', 'string', 'max:1000'],
        ]);

        if ($validated['status'] === 'berhasil') {
            if (strcasecmp((string) $validated['nim'], $applicant->nim) !== 0) {
                return response()->json([
                    'message' => 'NIM tidak sama dengan data PMB (' . $applicant->nim . '). Tarik ulang data mahasiswa ini.',
                ], 409);
            }

            if ($applicant->sync_status === 'sudah_sinkron') {
                return response()->json(['message' => 'Sudah dikonfirmasi sebelumnya.', 'data' => SiakadPayload::student($applicant)]);
            }

            $this->record($request, $applicant, $validated, [
                'sync_status' => 'sudah_sinkron',
                'synced_at' => now(),
            ], 'success', 'Data mahasiswa ' . $applicant->nim . ' sudah ditarik SIAKAD.');

            return response()->json(['message' => 'Konfirmasi diterima.', 'data' => SiakadPayload::student($applicant->refresh())]);
        }

        if ($applicant->sync_status === 'sudah_sinkron') {
            return response()->json(['message' => 'Mahasiswa sudah dikonfirmasi berhasil, status tidak dapat diubah menjadi gagal.'], 409);
        }

        $this->record($request, $applicant, $validated, ['sync_status' => 'gagal_sinkron'], 'failed', $validated['pesan']);

        return response()->json(['message' => 'Laporan gagal diterima. Admin PMB akan memperbaiki data lalu menyiapkannya kembali.']);
    }

    private function record(Request $request, Applicant $applicant, array $validated, array $changes, string $status, string $message): void
    {
        DB::transaction(function () use ($request, $applicant, $validated, $changes, $status, $message) {
            $payload = SiakadPayload::build($applicant);

            $applicant->update($changes);

            IntegrationLog::create([
                'applicant_id' => $applicant->id,
                'system_name' => 'SIAKAD',
                'direction' => 'inbound',
                'endpoint' => '/' . $request->path(),
                'method' => 'POST',
                'status' => $status,
                'request_payload' => $payload,
                'response_payload' => $status === 'success'
                    ? ['message' => $message, 'nim' => $applicant->nim, 'id_mahasiswa' => $validated['id_mahasiswa'] ?? null]
                    : null,
                'error_message' => $status === 'failed' ? $message : null,
                'processed_at' => now(),
            ]);
        });
    }

    private function students()
    {
        return Applicant::query()
            ->with(['pmbYear', 'admissionWave', 'studyProgram', 'acceptedStudyProgram', 'classType', 'address', 'education', 'parentData', 'reRegistration'])
            ->where('selection_status', 'diterima')
            ->where('re_registration_status', 'daftar_ulang_valid')
            ->whereNotNull('nim');
    }

    private function isVisible(Applicant $applicant): bool
    {
        return $applicant->selection_status === 'diterima'
            && $applicant->re_registration_status === 'daftar_ulang_valid'
            && filled($applicant->nim)
            && in_array($applicant->sync_status, self::VISIBLE_STATUSES, true);
    }

    private function notFound()
    {
        return response()->json(['message' => 'Mahasiswa tidak ditemukan atau belum siap ditarik.'], 404);
    }
}
