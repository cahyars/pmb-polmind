<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\FeeComponent;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PmbYear;
use App\Models\RegistrationSequence;
use App\Services\RegistrationNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CamabaAuthController extends Controller
{
    public function showRegister()
    {
        $activePmbYear = PmbYear::where('is_active', true)->first();

        $activeWave = AdmissionWave::current($activePmbYear);

        $registrationFee = FeeComponent::registrationFee()?->amount ?? $activeWave?->registration_fee;

        return view('auth.register', compact('activePmbYear', 'activeWave', 'registrationFee'));
    }

    public function register(Request $request)
    {
        $request->merge([
            'full_name' => mb_strtoupper(trim((string) $request->input('full_name'))),
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:applicants,email'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{9,15}$/'],
            'source_information' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'phone.regex' => 'Nomor WhatsApp hanya boleh berisi angka (9-15 digit).',
        ]);

        $activePmbYear = PmbYear::where('is_active', true)->first();
        $activeWave = AdmissionWave::current($activePmbYear);

        if (! $activePmbYear || ! $activeWave) {
            return back()
                ->withErrors('Pendaftaran belum dibuka karena Tahun PMB atau Gelombang aktif belum tersedia.')
                ->withInput();
        }

        $applicant = DB::transaction(function () use ($validated, $activePmbYear, $activeWave) {
            $registrationNumber = RegistrationNumber::temporary($activePmbYear);

            $applicant = Applicant::create([
                'pmb_year_id' => $activePmbYear->id,
                'admission_wave_id' => $activeWave->id,

                'registration_number' => $registrationNumber,
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'phone' => $validated['phone'],

                'source_information' => $validated['source_information'] ?? null,

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

    private function createRegistrationInvoice(Applicant $applicant, AdmissionWave $activeWave): void
    {
        $registrationFee = FeeComponent::registrationFee();

        $amount = $registrationFee?->amount ?? $activeWave->registration_fee ?? 0;

        $invoice = Invoice::create([
            'applicant_id' => $applicant->id,
            'invoice_number' => 'INV/PMB/' . $applicant->pmbYear->year . '/'
                . str_pad(RegistrationSequence::next($applicant->pmb_year_id, 'invoice-pendaftaran'), 5, '0', STR_PAD_LEFT),
            'type' => 'registration',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'total_amount' => $amount,
            'status' => 'unpaid',
            'note' => 'Biaya formulir pendaftaran PMB.',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'fee_component_id' => $registrationFee?->id,
            'name' => $registrationFee?->name ?? 'Biaya Formulir Pendaftaran',
            'amount' => $amount,
            'quantity' => 1,
            'subtotal' => $amount,
            'sort_order' => 1,
        ]);
    }
}