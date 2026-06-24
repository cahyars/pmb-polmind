<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\ApplicantDocument;
use App\Models\DocumentType;
use App\Models\FeeComponent;
use App\Models\FollowUp;
use App\Models\IntegrationLog;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\ReRegistration;
use App\Models\Selection;
use Illuminate\Database\Seeder;

class PmbTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $applicant = Applicant::where('registration_number', 'PMB20260001')->first();

        if (! $applicant) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Applicant Documents
        |--------------------------------------------------------------------------
        */

        $documents = [
            [
                'document_code' => 'PAS_FOTO',
                'file_name' => 'pas-foto-ahmad.jpg',
                'file_path' => 'applicant-documents/PMB20260001/pas-foto-ahmad.jpg',
                'file_extension' => 'jpg',
                'file_size_kb' => 512,
                'status' => 'diterima',
                'admin_note' => null,
                'uploaded_at' => now()->subDays(2),
                'verified_at' => now()->subDay(),
                'verified_by_name' => 'Admin PMB',
            ],
            [
                'document_code' => 'IDENTITAS',
                'file_name' => 'ktp-ahmad.pdf',
                'file_path' => 'applicant-documents/PMB20260001/ktp-ahmad.pdf',
                'file_extension' => 'pdf',
                'file_size_kb' => 760,
                'status' => 'menunggu_verifikasi',
                'admin_note' => null,
                'uploaded_at' => now()->subDay(),
                'verified_at' => null,
                'verified_by_name' => null,
            ],
            [
                'document_code' => 'KK',
                'file_name' => 'kk-ahmad.jpg',
                'file_path' => 'applicant-documents/PMB20260001/kk-ahmad.jpg',
                'file_extension' => 'jpg',
                'file_size_kb' => 900,
                'status' => 'ditolak',
                'admin_note' => 'Foto Kartu Keluarga kurang jelas. Mohon upload ulang dengan resolusi lebih baik.',
                'uploaded_at' => now()->subDays(2),
                'verified_at' => now()->subDay(),
                'verified_by_name' => 'Admin PMB',
            ],
        ];

        foreach ($documents as $document) {
            $documentType = DocumentType::where('code', $document['document_code'])->first();

            if (! $documentType) {
                continue;
            }

            ApplicantDocument::updateOrCreate(
                [
                    'applicant_id' => $applicant->id,
                    'document_type_id' => $documentType->id,
                ],
                [
                    'file_name' => $document['file_name'],
                    'file_path' => $document['file_path'],
                    'file_extension' => $document['file_extension'],
                    'file_size_kb' => $document['file_size_kb'],
                    'status' => $document['status'],
                    'admin_note' => $document['admin_note'],
                    'uploaded_at' => $document['uploaded_at'],
                    'verified_at' => $document['verified_at'],
                    'verified_by_name' => $document['verified_by_name'],
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Registration Invoice
        |--------------------------------------------------------------------------
        */

        $registrationInvoice = Invoice::updateOrCreate(
            ['invoice_number' => 'INV/PMB/2026/0001'],
            [
                'applicant_id' => $applicant->id,
                'type' => 'registration',
                'issue_date' => now()->subDays(3)->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
                'total_amount' => 350000,
                'status' => 'waiting_verification',
                'note' => 'Invoice biaya pendaftaran PMB 2026.',
            ]
        );

        $registrationFee = FeeComponent::where('code', 'PENDAFTARAN')->first();

        InvoiceItem::updateOrCreate(
            [
                'invoice_id' => $registrationInvoice->id,
                'name' => 'Biaya Pendaftaran',
            ],
            [
                'fee_component_id' => $registrationFee?->id,
                'amount' => 350000,
                'quantity' => 1,
                'subtotal' => 350000,
                'sort_order' => 1,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Payment
        |--------------------------------------------------------------------------
        */

        Payment::updateOrCreate(
            ['payment_number' => 'PAY/PMB/2026/0001'],
            [
                'applicant_id' => $applicant->id,
                'invoice_id' => $registrationInvoice->id,
                'transfer_date' => now()->subDay()->toDateString(),
                'sender_name' => 'Ahmad Fauzi',
                'sender_bank' => 'BCA',
                'amount' => 350000,
                'proof_file_name' => 'bukti-transfer-ahmad.jpg',
                'proof_file_path' => 'payment-proofs/PMB20260001/bukti-transfer-ahmad.jpg',
                'status' => 'waiting_verification',
                'admin_note' => null,
                'verified_at' => null,
                'verified_by_name' => null,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Selection
        |--------------------------------------------------------------------------
        */

        Selection::updateOrCreate(
            ['applicant_id' => $applicant->id],
            [
                'test_score' => null,
                'interview_score' => null,
                'final_score' => null,
                'status' => 'belum_diseleksi',
                'note' => 'Menunggu validasi berkas dan pembayaran.',
                'decided_at' => null,
                'decided_by_name' => null,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Re-registration Invoice & Data
        |--------------------------------------------------------------------------
        | Ini dibuat sebagai dummy awal. Nanti pada flow asli, invoice daftar ulang
        | baru dibuat setelah camaba dinyatakan diterima.
        */

        $reRegistrationInvoice = Invoice::updateOrCreate(
            ['invoice_number' => 'INV/DU/2026/0001'],
            [
                'applicant_id' => $applicant->id,
                'type' => 're_registration',
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(14)->toDateString(),
                'total_amount' => 11500000,
                'status' => 'unpaid',
                'note' => 'Dummy invoice daftar ulang. Pada flow asli dibuat setelah camaba diterima.',
            ]
        );

        $reRegistrationFees = FeeComponent::where('type', 're_registration')
            ->orderBy('sort_order')
            ->get();

        foreach ($reRegistrationFees as $fee) {
            InvoiceItem::updateOrCreate(
                [
                    'invoice_id' => $reRegistrationInvoice->id,
                    'name' => $fee->name,
                ],
                [
                    'fee_component_id' => $fee->id,
                    'amount' => $fee->amount,
                    'quantity' => 1,
                    'subtotal' => $fee->amount,
                    'sort_order' => $fee->sort_order,
                ]
            );
        }

        ReRegistration::updateOrCreate(
            ['applicant_id' => $applicant->id],
            [
                'invoice_id' => $reRegistrationInvoice->id,
                'status' => 'belum_daftar_ulang',
                'deadline_date' => now()->addDays(14)->toDateString(),
                'validated_at' => null,
                'validated_by_name' => null,
                'ready_sync_at' => null,
                'admin_note' => null,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Follow Up
        |--------------------------------------------------------------------------
        */

        FollowUp::updateOrCreate(
            [
                'applicant_id' => $applicant->id,
                'contacted_at' => now()->subDay()->startOfHour(),
            ],
            [
                'status' => 'tertarik',
                'priority' => 'tinggi',
                'contact_method' => 'whatsapp',
                'next_follow_up_date' => now()->addDays(2)->toDateString(),
                'note' => 'Camaba sudah dihubungi via WhatsApp. Masih perlu melengkapi ulang Kartu Keluarga dan menunggu verifikasi pembayaran.',
                'officer_name' => 'Admin PMB',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Integration Log
        |--------------------------------------------------------------------------
        */

        IntegrationLog::updateOrCreate(
            [
                'applicant_id' => $applicant->id,
                'endpoint' => '/api/pmb/applicants/ready-sync',
                'method' => 'GET',
            ],
            [
                'system_name' => 'SIAKAD',
                'direction' => 'outbound',
                'status' => 'pending',
                'request_payload' => [
                    'registration_number' => $applicant->registration_number,
                    'sync_status' => $applicant->sync_status,
                ],
                'response_payload' => null,
                'error_message' => null,
                'processed_at' => null,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Update Applicant Summary Status
        |--------------------------------------------------------------------------
        */

        $applicant->update([
            'document_status' => 'sebagian_upload',
            'payment_status' => 'menunggu_verifikasi',
            'selection_status' => 'belum_diseleksi',
            're_registration_status' => 'belum_daftar_ulang',
            'sync_status' => 'belum_siap',
        ]);
    }
}