<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\ClassType;
use App\Models\FeeComponent;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PmbYear;
use App\Models\StudyProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CamabaAuthController extends Controller
{
    public function showRegister()
    {
        $activePmbYear = PmbYear::where('is_active', true)->first();

        $activeWave = AdmissionWave::where('is_active', true)
            ->when($activePmbYear, function ($query) use ($activePmbYear) {
                $query->where('pmb_year_id', $activePmbYear->id);
            })
            ->first();

        $studyPrograms = StudyProgram::where('is_active', true)
            ->orderBy('code')
            ->get();

        $classTypes = ClassType::where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('auth.register', compact(
            'activePmbYear',
            'activeWave',
            'studyPrograms',
            'classTypes'
        ));
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:applicants,email'],
            'phone' => ['required', 'string', 'max:30'],
            'study_program_id' => ['required', 'exists:study_programs,id'],
            'class_type_id' => ['required', 'exists:class_types,id'],
            'registration_path' => ['required', 'in:umum,prestasi,undangan'],
            'source_information' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $activePmbYear = PmbYear::where('is_active', true)->first();
        $activeWave = AdmissionWave::where('is_active', true)
            ->when($activePmbYear, function ($query) use ($activePmbYear) {
                $query->where('pmb_year_id', $activePmbYear->id);
            })
            ->first();

        if (! $activePmbYear || ! $activeWave) {
            return back()
                ->withErrors('Pendaftaran belum dibuka karena Tahun PMB atau Gelombang aktif belum tersedia.')
                ->withInput();
        }

        $applicant = DB::transaction(function () use ($validated, $activePmbYear, $activeWave) {
            $registrationNumber = $this->generateRegistrationNumber($activePmbYear);

            $applicant = Applicant::create([
                'pmb_year_id' => $activePmbYear->id,
                'admission_wave_id' => $activeWave->id,
                'study_program_id' => $validated['study_program_id'],
                'second_study_program_id' => null,
                'class_type_id' => $validated['class_type_id'],

                'registration_number' => $registrationNumber,
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'phone' => $validated['phone'],

                'source_information' => $validated['source_information'] ?? null,
                'registration_path' => $validated['registration_path'],

                'registration_status' => 'registrasi_awal',
                'document_status' => 'belum_upload',
                'payment_status' => 'belum_bayar',
                'selection_status' => 'belum_diseleksi',
                're_registration_status' => 'belum_daftar_ulang',
                'sync_status' => 'belum_siap',
            ]);

            $this->createRegistrationInvoice($applicant, $activeWave);

            return $applicant;
        });

        Auth::guard('applicant')->login($applicant);

        $request->session()->regenerate();

        return redirect('/camaba/dashboard')
            ->with('success', 'Registrasi berhasil. Nomor pendaftaran Anda: ' . $applicant->registration_number);
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::guard('applicant')->attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return redirect('/camaba/dashboard')
                ->with('success', 'Berhasil login.');
        }

        return back()
            ->withErrors('Email atau password tidak sesuai.')
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('applicant')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Anda berhasil logout.');
    }

    private function generateRegistrationNumber(PmbYear $pmbYear): string
    {
        $count = Applicant::withTrashed()
            ->where('pmb_year_id', $pmbYear->id)
            ->lockForUpdate()
            ->count();

        $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        return 'PMB' . $pmbYear->year . $sequence;
    }

    private function createRegistrationInvoice(Applicant $applicant, AdmissionWave $activeWave): void
    {
        $registrationFee = FeeComponent::where('code', 'PENDAFTARAN')->first();

        $amount = $registrationFee?->amount ?? $activeWave->registration_fee ?? 0;

        $invoice = Invoice::create([
            'applicant_id' => $applicant->id,
            'invoice_number' => 'INV/PMB/' . now()->format('Y') . '/' . $applicant->registration_number,
            'type' => 'registration',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'total_amount' => $amount,
            'status' => 'unpaid',
            'note' => 'Invoice biaya pendaftaran PMB.',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'fee_component_id' => $registrationFee?->id,
            'name' => $registrationFee?->name ?? 'Biaya Pendaftaran',
            'amount' => $amount,
            'quantity' => 1,
            'subtotal' => $amount,
            'sort_order' => 1,
        ]);
    }
}