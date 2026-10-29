<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\FollowUp;
use App\Models\Invoice;
use App\Models\ReRegistration;
use App\Models\StudyProgram;
use App\Services\LoginInfoMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApplicantController extends Controller
{
    public function index(Request $request)
    {
        $totalApplicants = Applicant::count();

        $biodataComplete = Applicant::where('registration_status', 'biodata_lengkap')->count();

        $incompleteApplicants = Applicant::where('registration_status', '!=', 'biodata_lengkap')->count();

        $validReRegistrations = ReRegistration::where('status', 'valid')->count();

        $registrationPathStats = Applicant::query()
            ->selectRaw('registration_path, count(*) as total')
            ->groupBy('registration_path')
            ->pluck('total', 'registration_path');

        $waves = AdmissionWave::orderBy('id')->get();

        $studyPrograms = StudyProgram::where('is_active', true)
            ->orderBy('code')
            ->get();

        // Tandai camaba baru sudah dilihat (badge menu Data Camaba).
        $request->user('web')?->forceFill(['applicants_seen_at' => now()])->save();

        $applicants = Applicant::query()
            ->with([
                'pmbYear',
                'admissionWave',
                'studyProgram',
                'secondStudyProgram',
                'classType',
                'education',
                'reRegistration',
                'registrationInvoice',
                'photoDocument',
            ])
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = $request->keyword;

                $query->where(function ($q) use ($keyword) {
                    $q->where('registration_number', 'like', "%{$keyword}%")
                        ->orWhere('full_name', 'like', "%{$keyword}%")
                        ->orWhere('nik', 'like', "%{$keyword}%")
                        ->orWhere('nisn', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%")
                        ->orWhereHas('education', function ($educationQuery) use ($keyword) {
                            $educationQuery->where('school_name', 'like', "%{$keyword}%")
                                ->orWhere('school_npsn', 'like', "%{$keyword}%");
                        });
                });
            })
            ->when($request->filled('wave'), function ($query) use ($request) {
                $query->where('admission_wave_id', $request->wave);
            })
            ->when($request->filled('study_program'), function ($query) use ($request) {
                $query->where('study_program_id', $request->study_program);
            })
            ->when($request->filled('registration_path'), function ($query) use ($request) {
                $query->where('registration_path', $request->registration_path);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('registration_status', $request->status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.applicants.index', compact(
            'totalApplicants',
            'biodataComplete',
            'incompleteApplicants',
            'validReRegistrations',
            'registrationPathStats',
            'waves',
            'studyPrograms',
            'applicants'
        ));
    }

    public function show(Request $request, string $registration_number)
    {
        $applicant = Applicant::query()
            ->with([
                'pmbYear',
                'admissionWave',
                'studyProgram',
                'secondStudyProgram',
                'classType',
                'address',
                'education',
                'parentData',
                'documents.documentType',
                'photoDocument',
                'registrationInvoice.items.feeComponent',
                'registrationInvoice.payments',
                'reRegistrationInvoice.items.feeComponent',
                'reRegistrationInvoice.payments',
                'reRegistrationInvoice.installments',
                'selection.scores.component',
                'acceptedStudyProgram',
                'reRegistration.invoice',
                'followUps',
                'latestFollowUp',
                'integrationLogs',
            ])
            ->where('registration_number', $registration_number)
            ->firstOrFail();

        $installmentRows = $applicant->reRegistrationInvoice && $request->user('web')?->isAdmin()
            ? InstallmentController::formRows($applicant, $applicant->reRegistrationInvoice)
            : collect();

        $loginPin = LoginInfoMessage::activePin($applicant);

        return view('admin.applicants.show', [
            'applicant' => $applicant,
            'installmentRows' => $installmentRows,
            'loginPin' => $loginPin,
            'loginInfoText' => $loginPin ? LoginInfoMessage::text($applicant, $loginPin) : null,
        ]);
    }

    /**
     * Camaba lupa password: kembalikan ke PIN tanggal lahir (ddmmyyyy), lalu admin
     * mengirimkannya lewat tombol Kirim Info Login (hanya ke nomor HP terdaftar).
     */
    public function resetPassword(string $registration_number)
    {
        $applicant = Applicant::where('registration_number', $registration_number)->firstOrFail();

        $pin = LoginInfoMessage::defaultPin($applicant);

        if (! $pin) {
            return back()->withErrors('PIN tidak dapat direset karena tanggal lahir ' . $applicant->full_name . ' belum diisi. Lengkapi biodata camaba terlebih dahulu.');
        }

        DB::transaction(function () use ($applicant, $pin) {
            $applicant->password = $pin;
            $applicant->setRememberToken(null);
            $applicant->save();

            FollowUp::create([
                'applicant_id' => $applicant->id,
                'status' => 'sudah_dihubungi',
                'priority' => 'sedang',
                'contact_method' => 'direct',
                'contacted_at' => now(),
                'note' => 'Password camaba direset ke PIN tanggal lahir.',
                'officer_name' => $this->actorName(),
            ]);
        });

        return back()
            ->with('success', 'PIN ' . $applicant->full_name . ' direset ke tanggal lahir. Kirim info login ke WhatsApp camaba.')
            ->with('login_reset', true);
    }

    /**
     * Buka WhatsApp camaba dengan pesan Informasi Pendaftar (ID Pendaftar + PIN),
     * dan catat sebagai follow up supaya terlihat kapan info login terakhir dikirim.
     */
    public function sendLoginInfo(string $registration_number)
    {
        $applicant = Applicant::where('registration_number', $registration_number)->firstOrFail();

        $pin = LoginInfoMessage::activePin($applicant);

        if (! $pin) {
            return back()->withErrors('Info login tidak dapat dikirim karena ' . $applicant->full_name . ' sudah memakai password sendiri (bukan tanggal lahir) atau tanggal lahir belum diisi.');
        }

        $url = LoginInfoMessage::whatsappUrl($applicant, LoginInfoMessage::text($applicant, $pin));

        if (! $url) {
            return back()->withErrors('Nomor HP/WhatsApp ' . $applicant->full_name . ' belum diisi.');
        }

        FollowUp::create([
            'applicant_id' => $applicant->id,
            'status' => 'sudah_dihubungi',
            'priority' => 'sedang',
            'contact_method' => 'whatsapp',
            'contacted_at' => now(),
            'note' => 'Mengirim informasi login (ID Pendaftar dan PIN) lewat WhatsApp.',
            'officer_name' => $this->actorName(),
        ]);

        return redirect()->away($url);
    }

    /**
     * Gratiskan formulir pendaftaran atas perintah pimpinan, contoh untuk
     * pendaftar dari asal sekolah tertentu.
     */
    public function waiveRegistrationFee(Request $request, string $registration_number)
    {
        $applicant = Applicant::where('registration_number', $registration_number)->firstOrFail();

        $validated = $request->validate([
            'waiver_reason' => ['required', 'string', 'max:1000'],
        ], [], [
            'waiver_reason' => 'dasar pembebasan',
        ]);

        $waived = DB::transaction(function () use ($applicant, $request, $validated) {
            $invoice = Invoice::query()
                ->where('applicant_id', $applicant->id)
                ->where('type', 'registration')
                ->lockForUpdate()
                ->first();

            if (! $invoice || ! $invoice->canBeWaived()) {
                return false;
            }

            $invoice->waive($validated['waiver_reason'], $this->actorName());

            return true;
        });

        if (! $waived) {
            return back()->withErrors('Formulir tidak dapat digratiskan karena sudah dibayar, sedang menunggu verifikasi pembayaran, atau sudah dibebaskan.');
        }

        return back()->with('success', 'Biaya formulir ' . $applicant->full_name . ' berhasil dibebaskan. Camaba dapat langsung mengisi biodata.');
    }

    /**
     * Hapus permanen akun peminat yang belum membayar formulir. Nomor pendaftar
     * dan invoice-nya bisa dipakai lagi oleh pendaftar berikutnya.
     */
    public function destroy(string $registration_number)
    {
        $applicant = Applicant::where('registration_number', $registration_number)->firstOrFail();

        if (! $applicant->isDeletable()) {
            return back()->withErrors('Camaba ' . $applicant->full_name . ' tidak dapat dihapus karena pembayaran formulir sudah diproses.');
        }

        $applicant->purge();

        return back()->with('success', 'Data camaba ' . $applicant->full_name . ' (' . $applicant->registration_number . ') berhasil dihapus.');
    }
}