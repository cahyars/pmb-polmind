<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ReRegistration;

/**
 * Menyamakan status invoice, camaba, dan daftar ulang dengan pembayaran yang
 * sudah masuk. Dipanggil setiap kali pembayaran atau jadwal cicilan berubah.
 */
class InvoiceStatus
{
    public static function refresh(Invoice $invoice, string $actorName, ?string $adminNote = null): void
    {
        $invoice->load(['applicant', 'installments']);
        $applicant = $invoice->applicant;

        $payments = Payment::where('invoice_id', $invoice->id);

        $totalValidPaid = (float) (clone $payments)->where('status', 'valid')->sum('amount');
        $hasWaitingPayment = (clone $payments)->where('status', 'waiting_verification')->exists();
        $hasRejectedPayment = (clone $payments)->where('status', 'rejected')->exists();

        $isFullyPaid = $totalValidPaid >= (float) $invoice->total_amount;
        $hasPartialPaid = $totalValidPaid > 0;

        $invoiceStatus = 'unpaid';

        if ($isFullyPaid) {
            $invoiceStatus = 'paid';
        } elseif ($hasPartialPaid) {
            $invoiceStatus = 'partial';
        } elseif ($hasWaitingPayment) {
            $invoiceStatus = 'waiting_verification';
        } elseif ($hasRejectedPayment) {
            $invoiceStatus = 'rejected';
        }

        $invoice->update([
            'status' => $invoiceStatus,
        ]);

        if ($invoice->type === 'registration') {
            $paymentStatus = 'belum_bayar';

            if ($isFullyPaid) {
                $paymentStatus = 'valid';
            } elseif ($hasWaitingPayment) {
                $paymentStatus = 'menunggu_verifikasi';
            } elseif (! $hasPartialPaid && $hasRejectedPayment) {
                $paymentStatus = 'ditolak';
            }

            $applicant->update([
                'payment_status' => $paymentStatus,
            ]);
        }

        if ($invoice->type === 're_registration') {
            // Camaba dengan cicilan sudah dianggap daftar ulang setelah angsuran ke-1 terbayar.
            $isReRegistered = $isFullyPaid
                || ($invoice->hasInstallments() && $hasPartialPaid && $totalValidPaid >= $invoice->firstInstallmentAmount());

            $reRegistrationStatus = 'belum_daftar_ulang';
            $syncStatus = 'belum_siap';

            if ($isReRegistered) {
                $reRegistrationStatus = 'daftar_ulang_valid';
                // Siap ditarik SIAKAD setelah NIM dibubuhkan; proses sinkron yang sudah berjalan tidak diulang.
                $syncStatus = match (true) {
                    in_array($applicant->sync_status, ['proses_sinkron', 'sudah_sinkron', 'gagal_sinkron'], true) => $applicant->sync_status,
                    filled($applicant->nim) => 'siap_sinkron',
                    default => 'belum_siap',
                };
            } elseif ($hasWaitingPayment) {
                $reRegistrationStatus = 'menunggu_verifikasi';
            }

            $current = ReRegistration::where('applicant_id', $applicant->id)->first();

            ReRegistration::updateOrCreate(
                ['applicant_id' => $applicant->id],
                [
                    'invoice_id' => $invoice->id,
                    'status' => $isReRegistered ? 'valid' : $reRegistrationStatus,
                    'validated_at' => $isReRegistered ? ($current?->validated_at ?? now()) : null,
                    'validated_by_name' => $isReRegistered ? ($current?->validated_by_name ?? $actorName) : null,
                    'ready_sync_at' => $isReRegistered && filled($applicant->nim) ? ($current?->ready_sync_at ?? now()) : null,
                    'admin_note' => match (true) {
                        $isFullyPaid => null,
                        $isReRegistered => 'Daftar ulang valid. Sisa tagihan dibayar sesuai jadwal pembayaran.',
                        default => $adminNote ?? ($hasPartialPaid ? 'Pembayaran sudah masuk sebagian. Menunggu pelunasan sisa tagihan.' : null),
                    },
                ]
            );

            $applicant->update([
                're_registration_status' => $reRegistrationStatus,
                'sync_status' => $syncStatus,
            ]);
        }
    }
}
