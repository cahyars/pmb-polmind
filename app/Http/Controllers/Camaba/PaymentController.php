<?php

namespace App\Http\Controllers\Camaba;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ReRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function index()
    {
        $applicant = Auth::guard('applicant')->user();

        $applicant->load([
            'registrationInvoice.items',
            'registrationInvoice.payments',
            'registrationInvoice.latestPayment',
            'reRegistrationInvoice.items',
            'reRegistrationInvoice.payments',
            'reRegistrationInvoice.latestPayment',
        ]);

        $invoices = collect([
            $applicant->registrationInvoice,
            $applicant->reRegistrationInvoice,
        ])->filter();

        return view('camaba.pembayaran', compact('applicant', 'invoices'));
    }

    public function uploadProof(Request $request, Invoice $invoice)
    {
        $applicant = Auth::guard('applicant')->user();

        abort_if($invoice->applicant_id !== $applicant->id, 403);

        if ($invoice->status === 'paid') {
            return back()->withErrors('Tagihan ini sudah lunas dan tidak dapat upload ulang bukti pembayaran.');
        }

        $validated = $request->validate([
            'sender_name' => ['required', 'string', 'max:255'],
            'sender_bank' => ['required', 'string', 'max:100'],
            'transfer_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:1'],
            'proof_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);

        $totalValidPaid = Payment::where('invoice_id', $invoice->id)
            ->where('status', 'valid')
            ->sum('amount');

        $remainingAmount = (float) $invoice->total_amount - (float) $totalValidPaid;
        $paidAmount = (float) $validated['amount'];

        if ($remainingAmount <= 0) {
            return back()->withErrors('Tagihan ini sudah lunas.')->withInput();
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

        if ($existingPayment?->proof_file_path && Storage::disk('public')->exists($existingPayment->proof_file_path)) {
            Storage::disk('public')->delete($existingPayment->proof_file_path);
        }

        $folder = 'payment-proofs/' . $applicant->registration_number;

        $safeInvoiceNumber = Str::slug(str_replace('/', '-', $invoice->invoice_number));

        $filename = $safeInvoiceNumber . '_' . now()->format('YmdHis') . '.' . $file->getClientOriginalExtension();

        $filePath = $file->storeAs($folder, $filename, 'public');

        $paymentData = [
            'applicant_id' => $applicant->id,
            'invoice_id' => $invoice->id,
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

        return back()->with('success', 'Bukti pembayaran berhasil diupload dan menunggu verifikasi admin.');
    }

    private function generatePaymentNumber(Invoice $invoice): string
    {
        $prefix = $invoice->type === 're_registration' ? 'PAY/DU' : 'PAY/PMB';

        $count = Payment::whereYear('created_at', now()->year)->count() + 1;

        return $prefix . '/' . now()->format('Y') . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}