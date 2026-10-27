<?php

namespace App\Http\Controllers\Camaba;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\RegistrationSequence;
use App\Models\ReRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index()
    {
        $applicant = Auth::guard('applicant')->user();

        $applicant->registrationInvoice?->syncRegistrationFee();

        $applicant->load([
            'registrationInvoice.items',
            'registrationInvoice.payments',
            'registrationInvoice.latestPayment',
            'reRegistrationInvoice.items',
            'reRegistrationInvoice.payments',
            'reRegistrationInvoice.latestPayment',
            'registrationInvoice.payments.paymentMethod',
            'reRegistrationInvoice.payments.paymentMethod',
        ]);

        $paymentMethods = PaymentMethod::active()->get();

        $invoices = collect([
            $applicant->registrationInvoice,
            $applicant->reRegistrationInvoice,
        ])->filter();

        return view('camaba.pembayaran', compact('applicant', 'invoices', 'paymentMethods'));
    }

    public function uploadProof(Request $request, Invoice $invoice)
    {
        $applicant = Auth::guard('applicant')->user();

        abort_if($invoice->applicant_id !== $applicant->id, 403);

        if ($invoice->status === 'paid') {
            return back()->withErrors('Tagihan ini sudah lunas dan tidak dapat upload ulang bukti pembayaran.');
        }

        $invoice->syncRegistrationFee();
        $invoice->refresh();

        $hasPaymentMethods = PaymentMethod::where('is_active', true)->exists();

        $validated = $request->validate([
            'payment_method_id' => [
                Rule::requiredIf($hasPaymentMethods),
                'nullable',
                Rule::exists('payment_methods', 'id')->where('is_active', true),
            ],
            'sender_name' => ['required', 'string', 'max:255'],
            'sender_bank' => ['required', 'string', 'max:100'],
            'transfer_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:1'],
            'proof_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ], [
            'payment_method_id.required' => 'Pilih rekening/tujuan pembayaran yang Anda transfer.',
        ], [
            'payment_method_id' => 'tujuan pembayaran',
            'sender_name' => 'nama pengirim',
            'sender_bank' => 'bank/e-wallet pengirim',
            'transfer_date' => 'tanggal transfer',
            'amount' => 'nominal bayar',
            'proof_file' => 'bukti pembayaran',
        ]);

        $totalValidPaid = Payment::where('invoice_id', $invoice->id)
            ->where('status', 'valid')
            ->sum('amount');

        $remainingAmount = (float) $invoice->total_amount - (float) $totalValidPaid;
        $paidAmount = (float) $validated['amount'];

        if ($remainingAmount <= 0) {
            return back()->withErrors('Tagihan ini sudah lunas.')->withInput();
        }

        if ($invoice->type === 'registration' && $paidAmount !== $remainingAmount) {
            return back()
                ->withErrors('Nominal pembayaran formulir harus sesuai tagihan: Rp' . number_format($remainingAmount, 0, ',', '.') . '.')
                ->withInput();
        }

        if ($paidAmount > $remainingAmount) {
            return back()
                ->withErrors(
                    'Nominal pembayaran melebihi sisa tagihan. Sisa tagihan saat ini: Rp' .
                    number_format($remainingAmount, 0, ',', '.')
                )
                ->withInput();
        }

        $existingPayment = Payment::where('applicant_id', $applicant->id)
            ->where('invoice_id', $invoice->id)
            ->where('status', 'waiting_verification')
            ->latest()
            ->first();

        if ($existingPayment) {
            return back()
                ->withErrors('Masih ada pembayaran yang menunggu verifikasi admin. Silakan tunggu validasi terlebih dahulu.')
                ->withInput();
        }

        $file = $request->file('proof_file');

        if ($existingPayment?->proof_file_path && Storage::disk('local')->exists($existingPayment->proof_file_path)) {
            Storage::disk('local')->delete($existingPayment->proof_file_path);
        }

        $folder = 'payment-proofs/' . $applicant->registration_number;

        $safeInvoiceNumber = Str::slug(str_replace('/', '-', $invoice->invoice_number));
        $extension = strtolower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension()));
        $filename = $safeInvoiceNumber . '_' . now()->format('YmdHis') . '_' . Str::random(6) . '.' . $extension;

        $filePath = $file->storeAs($folder, $filename, 'local');

        DB::transaction(function () use ($applicant, $invoice, $validated, $existingPayment, $filePath, $file) {
            $paymentData = [
                'applicant_id' => $applicant->id,
                'invoice_id' => $invoice->id,
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'payment_number' => $existingPayment?->payment_number ?? $this->generatePaymentNumber($invoice),
                'transfer_date' => $validated['transfer_date'],
                'sender_name' => $validated['sender_name'],
                'sender_bank' => $validated['sender_bank'],
                'amount' => $validated['amount'],
                'proof_file_name' => $file->getClientOriginalName(),
                'proof_file_path' => $filePath,
                'status' => 'waiting_verification',
                'admin_note' => null,
                'verified_at' => null,
                'verified_by_name' => null,
            ];

            Payment::create($paymentData);

            $invoice->update([
                'status' => 'waiting_verification',
                'note' => 'Bukti pembayaran sudah diupload dan menunggu verifikasi admin.',
            ]);

            if ($invoice->type === 'registration') {
                $applicant->update([
                    'payment_status' => 'menunggu_verifikasi',
                ]);
            }

            if ($invoice->type === 're_registration') {
                $applicant->update([
                    're_registration_status' => 'menunggu_verifikasi',
                    'sync_status' => 'belum_siap',
                ]);

                ReRegistration::updateOrCreate(
                    ['applicant_id' => $applicant->id],
                    [
                        'invoice_id' => $invoice->id,
                        'status' => 'menunggu_verifikasi',
                        'admin_note' => null,
                        'ready_sync_at' => null,
                    ]
                );
            }
        });

        return back()->with('payment_submitted', true);
    }

    private function generatePaymentNumber(Invoice $invoice): string
    {
        $invoice->loadMissing('applicant.pmbYear');

        $prefix = $invoice->type === 're_registration' ? 'PAY/DU' : 'PAY/PMB';
        $pmbYear = $invoice->applicant->pmbYear;

        $number = RegistrationSequence::next($pmbYear->id, $invoice->type === 're_registration' ? 'pembayaran-du' : 'pembayaran-pmb');

        return $prefix . '/' . $pmbYear->year . '/' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }
}