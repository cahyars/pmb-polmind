<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'invoice_id',
        'payment_method_id',
        'payment_number',
        'receipt_number',
        'transfer_date',
        'sender_name',
        'sender_bank',
        'amount',
        'proof_file_name',
        'proof_file_path',
        'status',
        'admin_note',
        'verified_at',
        'verified_by_name',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'verified_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    /**
     * Nomor pembayaran berikutnya: PAY/PMB/{TAHUN}/{URUT} untuk formulir, PAY/DU/... untuk daftar ulang.
     */
    public static function nextNumber(Invoice $invoice): string
    {
        $invoice->loadMissing('applicant.pmbYear');

        $prefix = $invoice->type === 're_registration' ? 'PAY/DU' : 'PAY/PMB';
        $pmbYear = $invoice->applicant->pmbYear;

        $number = RegistrationSequence::next($pmbYear->id, $invoice->type === 're_registration' ? 'pembayaran-du' : 'pembayaran-pmb');

        return $prefix . '/' . $pmbYear->year . '/' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}