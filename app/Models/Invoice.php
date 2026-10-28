<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'invoice_number',
        'type',
        'issue_date',
        'due_date',
        'total_amount',
        'status',
        'waived_at',
        'waived_by_name',
        'waiver_reason',
        'note',
    ];

    protected $casts = [
        'waived_at' => 'datetime',
        'issue_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Samakan nominal tagihan formulir dengan biaya di Master Data selama
     * belum ada pembayaran yang dikirim atau divalidasi.
     */
    public function syncRegistrationFee(): void
    {
        if ($this->type !== 'registration' || in_array($this->status, ['paid', 'partial', 'cancelled'], true)) {
            return;
        }

        $fee = FeeComponent::registrationFee();

        if (! $fee || (float) $fee->amount === (float) $this->total_amount) {
            return;
        }

        $hasPayment = $this->payments()->whereIn('status', ['waiting_verification', 'valid'])->exists();

        if ($hasPayment) {
            return;
        }

        $this->update(['total_amount' => $fee->amount]);

        $this->items()->update([
            'fee_component_id' => $fee->id,
            'name' => $fee->name,
            'amount' => $fee->amount,
            'quantity' => 1,
            'subtotal' => $fee->amount,
        ]);
    }

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isUnpaid(): bool
    {
        return $this->status === 'unpaid';
    }

    public function isWaitingVerification(): bool
    {
        return $this->status === 'waiting_verification';
    }

    public function isWaived(): bool
    {
        return $this->waived_at !== null;
    }

    /**
     * Formulir boleh digratiskan selama belum ada pembayaran yang dikirim atau divalidasi.
     */
    public function canBeWaived(): bool
    {
        return $this->type === 'registration'
            && ! $this->isWaived()
            && in_array($this->status, ['unpaid', 'rejected'], true)
            && ! $this->payments()->whereIn('status', ['waiting_verification', 'valid'])->exists();
    }

    /**
     * Bebaskan biaya formulir: tagihan dianggap lunas tanpa pembayaran
     * dan camaba langsung dapat melanjutkan ke biodata.
     */
    public function waive(string $reason, string $waivedBy): void
    {
        $this->update([
            'status' => 'paid',
            'waived_at' => now(),
            'waived_by_name' => $waivedBy,
            'waiver_reason' => $reason,
            'note' => 'Biaya formulir dibebaskan: ' . $reason,
        ]);

        $this->applicant->update([
            'payment_status' => 'valid',
        ]);
    }
}