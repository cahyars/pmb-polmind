<?php

namespace App\Services;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\ClassType;
use App\Models\FeeComponent;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PmbYear;
use App\Models\RegistrationSequence;

/**
 * Membuat akun camaba baru beserta nomor pendaftaran final dan invoice formulir.
 * Dipakai registrasi online dan form pendaftar di kampus. Panggil di dalam transaksi.
 */
class ApplicantRegistration
{
    public static function create(array $attributes, PmbYear $pmbYear, AdmissionWave $wave, ClassType $classType): Applicant
    {
        $applicant = new Applicant($attributes + [
            'registration_status' => 'registrasi_awal',
            'document_status' => 'belum_upload',
            'payment_status' => 'belum_bayar',
            'selection_status' => 'belum_diseleksi',
            're_registration_status' => 'belum_daftar_ulang',
            'sync_status' => 'belum_siap',
        ]);

        $applicant->pmb_year_id = $pmbYear->id;
        $applicant->admission_wave_id = $wave->id;
        $applicant->class_type_id = $classType->id;

        // Kelas sudah dipilih, sehingga nomor pendaftaran final langsung dibuat.
        $applicant->setRelation('pmbYear', $pmbYear);
        $applicant->setRelation('admissionWave', $wave);
        $applicant->registration_number = RegistrationNumber::final($applicant, $classType);
        $applicant->save();

        self::createRegistrationInvoice($applicant, $wave);

        return $applicant;
    }

    private static function createRegistrationInvoice(Applicant $applicant, AdmissionWave $wave): void
    {
        $registrationFee = FeeComponent::registrationFee();

        $amount = $registrationFee?->amount ?? $wave->registration_fee ?? 0;

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
