<?php

namespace App\Http\Controllers\Camaba;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
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
            'reRegistrationInvoice.items.feeComponent',
            'reRegistrationInvoice.payments',
            'reRegistrationInvoice.latestPayment',
            'registrationInvoice.payments.paymentMethod',
            'reRegistrationInvoice.payments.paymentMethod',
            'reRegistrationInvoice.installments',
            'registrationInvoice.payments.allocations',
            'reRegistrationInvoice.payments.allocations.feeComponent',
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
        $isReRegistration = $invoice->type === 're_registration';

        $validated = $request->validate([
            'payment_method_id' => [
                Rule::requiredIf($hasPaymentMethods),
                'nullable',
                Rule::exists('payment_methods', 'id')->where('is_active', true),
            ],
            'sender_name' => ['required', 'string', 'max:255'],
            'sender_bank' => ['required', 'string', 'max:100'],
            'transfer_date' => ['required', 'date'],
            'amount' => [Rule::requiredIf(! $isReRegistration), 'nullable', 'numeric', 'min:1'],
            // Daftar ulang: komponen yang dibayar (UKT, SPI, atau keduanya); nominal dihitung sistem.
            'components' => [Rule::requiredIf($isReRegistration), 'array', 'min:1'],
            'components.*' => ['integer'],
            'proof_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ], [
            'payment_method_id.required' => 'Pilih rekening/tujuan pembayaran yang Anda transfer.',
            'components.required' => 'Pilih komponen yang Anda bayar.',
        ], [
            'payment_method_id' => 'tujuan pembayaran',
            'sender_name' => 'nama pengirim',
            'sender_bank' => 'bank/e-wallet pengirim',
            'transfer_date' => 'tanggal transfer',
            'amount' => 'nominal bayar',
            'proof_file' => 'bukti pembayaran',
        ]);

        $allocations = [];

        if ($isReRegistration) {
            $balances = $invoice->componentBalances()->keyBy('fee_component_id');

            foreach (array_unique($validated['components']) as $componentId) {
                $balance = $balances->get((int) $componentId);

                if (! $balance) {
                    return back()->withErrors('Komponen pembayaran tidak valid.')->withInput();
                }

                if ($balance['due'] <= 0) {
                    return back()->withErrors($balance['item']->name . ' sudah lunas.')->withInput();
                }

                if ($balance['waiting'] > 0) {
                    return back()->withErrors('Pembayaran ' . $balance['item']->name . ' masih menunggu verifikasi admin.')->withInput();
                }

                $allocations[] = [
                    'fee_component_id' => $balance['fee_component_id'],
                    'name' => $balance['item']->name,
                    'amount' => $balance['due'],
                ];
            }

            $validated['amount'] = array_sum(array_column($allocations, 'amount'));
        } else {
            $totalValidPaid = Payment::where('invoice_id', $invoice->id)
                ->where('status', 'valid')
                ->sum('amount');

            $remainingAmount = (float) $invoice->total_amount - (float) $totalValidPaid;

            if ($remainingAmount <= 0) {
                return back()->withErrors('Tagihan ini sudah lunas.')->withInput();
            }

            if ((float) $validated['amount'] !== $remainingAmount) {
                return back()
                    ->withErrors('Nominal pembayaran formulir harus sesuai tagihan: Rp' . number_format($remainingAmount, 0, ',', '.') . '.')
                    ->withInput();
            }

            if (Payment::where('invoice_id', $invoice->id)->where('status', 'waiting_verification')->exists()) {
                return back()
                    ->withErrors('Masih ada pembayaran yang menunggu verifikasi admin. Silakan tunggu validasi terlebih dahulu.')
                    ->withInput();
            }
        }

        $file = $request->file('proof_file');

        $folder = 'payment-proofs/' . $applicant->registration_number;

        $safeInvoiceNumber = Str::slug(str_replace('/', '-', $invoice->invoice_number));
        $extension = strtolower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension()));
        $filename = $safeInvoiceNumber . '_' . now()->format('YmdHis') . '_' . Str::random(6) . '.' . $extension;

        $filePath = $file->storeAs($folder, $filename, 'local');

        DB::transaction(function () use ($applicant, $invoice, $validated, $allocations, $filePath, $file) {
            $paymentData = [
                'applicant_id' => $applicant->id,
                'invoice_id' => $invoice->id,
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'payment_number' => Payment::nextNumber($invoice),
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

            Payment::create($paymentData)->allocations()->createMany($allocations);

            $invoice->update([
                // Tagihan yang sudah terbayar sebagian tetap "Belum Lunas".
                'status' => $invoice->status === 'partial' ? 'partial' : 'waiting_verification',
                'note' => 'Bukti pembayaran sudah diupload dan menunggu verifikasi admin.',
            ]);

            if ($invoice->type === 'registration') {
                $applicant->update([
                    'payment_status' => 'menunggu_verifikasi',
                ]);
            }

            // Daftar ulang yang sudah valid (angsuran ke-1 lunas) tidak kembali menunggu verifikasi.
            if ($invoice->type === 're_registration' && $applicant->re_registration_status !== 'daftar_ulang_valid') {
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

        return back()->with('payment_submitted', $invoice->type);
    }
}
