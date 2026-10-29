<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SelectAdminPmbYear;
use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\ApplicantEducation;
use App\Models\ClassType;
use App\Models\FeeComponent;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PmbYear;
use App\Models\RefRegion;
use App\Models\RegistrationPath;
use App\Models\StudyProgram;
use App\Services\ApplicantBiodata;
use App\Services\ApplicantJourney;
use App\Services\ApplicantRegistration;
use App\Services\InvoiceStatus;
use App\Services\ReceiptNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Data Camaba > Tambah Pendaftar: admin/operator menginput pendaftar yang datang langsung
 * ke kampus dari formulir fisik. Password awal camaba = tanggal lahir (ddmmyyyy).
 */
class WalkInApplicantController extends Controller
{
    public function create()
    {
        $pmbYear = PmbYear::where('is_active', true)->first();

        if (! $pmbYear) {
            return redirect()->route('admin.applicants.index')->withErrors('Belum ada Tahun PMB aktif. Aktifkan Tahun PMB di Master Data terlebih dahulu.');
        }

        $waves = $this->waves($pmbYear);

        return view('admin.applicants.create', [
            'applicant' => new Applicant(['citizenship' => 'ID', 'special_need_id' => 0]),
            'pmbYear' => $pmbYear,
            'waves' => $waves,
            'defaultWaveId' => AdmissionWave::current($pmbYear)?->id ?? $waves->last()?->id,
            'studyPrograms' => StudyProgram::where('is_active', true)->orderBy('code')->get(),
            'classTypes' => ClassType::where('is_active', true)->orderBy('code')->get(),
            'registrationPaths' => RegistrationPath::active(),
            'references' => ApplicantBiodata::references(),
            'provinces' => RefRegion::where('level', RefRegion::LEVEL_PROVINCE)->orderBy('name')->get(['id', 'name']),
            'addressRegion' => RefRegion::chainFor(old('region_id')),
            'schoolRegion' => RefRegion::chainFor(old('school_region_id')),
            'schoolTypes' => ApplicantEducation::SCHOOL_TYPES,
            'paymentMethods' => PaymentMethod::where('is_active', true)->orderBy('type')->orderBy('provider')->get(),
            'registrationFee' => FeeComponent::registrationFee()?->amount,
        ]);
    }

    public function store(Request $request)
    {
        $pmbYear = PmbYear::where('is_active', true)->firstOrFail();
        $waves = $this->waves($pmbYear);

        ApplicantBiodata::prepare($request);

        $wave = $waves->firstWhere('id', (int) $request->input('admission_wave_id'));
        $manualSchool = $request->boolean('is_manual_school');

        $validated = $request->validate(
            [
                'admission_wave_id' => ['required', Rule::in($waves->pluck('id')->all())],
                'study_program_id' => ['required', Rule::exists('study_programs', 'id')->where('is_active', true)],
                'second_study_program_id' => ['nullable', 'different:study_program_id', Rule::exists('study_programs', 'id')->where('is_active', true)],
                'class_type_id' => ['required', Rule::exists('class_types', 'id')->where('is_active', true)],
                'registration_path' => ['required', Rule::in(RegistrationPath::active()->keys()->all())],
                'source_information' => ['nullable', Rule::in(Applicant::SOURCE_OPTIONS)],

                'major' => ['nullable', 'string', 'max:255'],
                'graduation_year' => ['nullable', 'integer', 'min:2000', 'max:' . (now()->year + 1)],
                'school_address' => ['nullable', 'string', 'max:500'],

                'registration_paid' => ['required', 'boolean'],
                'payment_method_id' => ['nullable', 'required_if:registration_paid,1', Rule::exists('payment_methods', 'id')->where('is_active', true)],
                'paid_at' => ['nullable', 'required_if:registration_paid,1', 'date', 'before_or_equal:today'],
                'payment_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            ]
            + ApplicantBiodata::personalRules($wave?->id)
            + ApplicantEducation::schoolRules($manualSchool)
            + ApplicantBiodata::addressRules(required: false)
            + ApplicantBiodata::parentRules(motherRequired: false),
            [
                'admission_wave_id.in' => 'Pilih gelombang pada Tahun PMB aktif.',
                'second_study_program_id.different' => 'Pilihan prodi kedua harus berbeda dengan pilihan pertama.',
                'payment_method_id.required_if' => 'Pilih tujuan pembayaran formulir.',
                'paid_at.required_if' => 'Isi tanggal pembayaran formulir.',
            ]
            + ApplicantBiodata::personalMessages($wave)
            + ApplicantEducation::schoolMessages()
            + ApplicantBiodata::addressMessages()
            + ApplicantBiodata::parentMessages(),
            [
                'payment_proof' => 'bukti pembayaran',
            ]
        );

        $password = Carbon::parse($validated['birth_date'])->format('dmY');

        $applicant = DB::transaction(function () use ($validated, $request, $pmbYear, $wave, $password) {
            $applicant = ApplicantRegistration::create(ApplicantBiodata::personalAttributes($validated) + [
                'study_program_id' => $validated['study_program_id'],
                'second_study_program_id' => ApplicantBiodata::blankToNull($validated['second_study_program_id'] ?? null),
                'registration_path' => $validated['registration_path'],
                'source_information' => $validated['source_information'] ?? null,
                'password' => $password,
                'registered_via' => 'kampus',
                'registered_by_name' => $this->actorName(),
            ], $pmbYear, $wave, ClassType::findOrFail($validated['class_type_id']));

            $applicant->education()->create(ApplicantEducation::schoolAttributes($validated) + [
                'major' => ApplicantBiodata::blankToNull($validated['major'] ?? null),
                'graduation_year' => ApplicantBiodata::blankToNull($validated['graduation_year'] ?? null),
                'school_address' => ApplicantBiodata::blankToNull($validated['school_address'] ?? null),
            ]);

            if (filled($validated['address'] ?? null)) {
                ApplicantBiodata::saveAddress($applicant, $validated);
            }

            if (filled($validated['mother_name'] ?? null) || filled($validated['father_name'] ?? null) || filled($validated['guardian_name'] ?? null)) {
                $applicant->parentData()->create(ApplicantBiodata::parentAttributes($validated));
            }

            if (ApplicantJourney::for($applicant->refresh())->biodataComplete()) {
                $applicant->update(['registration_status' => 'biodata_lengkap']);
            }

            if ($validated['registration_paid']) {
                $this->recordPayment($applicant, $validated, $request);
            }

            return $applicant;
        });

        // Pendaftar baru selalu masuk Tahun PMB aktif; tampilkan tahun itu di panel admin.
        $request->session()->put(SelectAdminPmbYear::SESSION_KEY, $pmbYear->id);

        return redirect()
            ->route('admin.applicants.show', $applicant->registration_number)
            ->with('success', 'Pendaftar ' . $applicant->full_name . ' berhasil ditambahkan dengan nomor pendaftaran ' . $applicant->registration_number . '.')
            ->with('walk_in_account', [
                'email' => $applicant->email,
                'password' => $password,
            ]);
    }

    /**
     * Pembayaran formulir yang diterima langsung di kampus dicatat sebagai pembayaran valid.
     */
    private function recordPayment(Applicant $applicant, array $validated, Request $request): void
    {
        $invoice = $applicant->registrationInvoice()->firstOrFail();
        $method = PaymentMethod::findOrFail($validated['payment_method_id']);
        $proof = $request->file('payment_proof');
        $path = $proof?->storeAs(
            'payment-proofs/' . $applicant->registration_number,
            Str::slug(str_replace('/', '-', $invoice->invoice_number)) . '_' . now()->format('YmdHis') . '_' . Str::random(6) . '.'
                . strtolower((string) ($proof->guessExtension() ?: $proof->getClientOriginalExtension())),
            'local'
        );

        $payment = Payment::create([
            'applicant_id' => $applicant->id,
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'payment_number' => Payment::nextNumber($invoice),
            'transfer_date' => $validated['paid_at'],
            'sender_name' => $applicant->full_name,
            'sender_bank' => $method->type === 'cash' ? 'Tunai' : $method->provider,
            'amount' => $invoice->total_amount,
            'proof_file_name' => $proof?->getClientOriginalName(),
            'proof_file_path' => $path,
            'status' => 'valid',
            'admin_note' => 'Dibayar saat mendaftar di kampus.',
            'verified_at' => now(),
            'verified_by_name' => $this->actorName(),
        ]);

        ReceiptNumber::assign($payment);

        InvoiceStatus::refresh($invoice, $this->actorName());
    }

    /**
     * Semua gelombang Tahun PMB aktif, termasuk yang sudah ditutup (pendaftar susulan).
     */
    private function waves(PmbYear $pmbYear): Collection
    {
        return AdmissionWave::withoutGlobalScope('admin_pmb_year')
            ->where('pmb_year_id', $pmbYear->id)
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();
    }
}
