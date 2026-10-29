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
        'installment_enabled_at',
        'installment_enabled_by_name',
        'installment_note',
    ];

    protected $casts = [
        'waived_at' => 'datetime',
        'installment_enabled_at' => 'datetime',
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

    public function installments()
    {
        return $this->hasMany(InvoiceInstallment::class)->orderBy('due_date')->orderBy('sequence')->orderBy('id');
    }

    /**
     * Skema cicilan hanya ada jika diaktifkan admin untuk camaba ini.
     */
    public function hasInstallments(): bool
    {
        return $this->installment_enabled_at !== null;
    }

    /**
     * Total angsuran ke-1 semua komponen; daftar ulang valid setelah nominal ini terbayar.
     */
    public function firstInstallmentAmount(): float
    {
        return (float) $this->installments->where('sequence', 1)->sum('amount');
    }

    public function validPaidAmount(): float
    {
        return (float) $this->payments->where('status', 'valid')->sum('amount');
    }

    /**
     * Jadwal angsuran beserta statusnya. Pembayaran valid dialokasikan
     * berurutan mulai dari jatuh tempo paling awal.
     *
     * @return \Illuminate\Support\Collection<int, array{installment: InvoiceInstallment, paid: float, remaining: float, status: string, overdue: bool}>
     */
    public function installmentSchedule(): \Illuminate\Support\Collection
    {
        $available = $this->validPaidAmount();

        return $this->installments->map(function (InvoiceInstallment $installment) use (&$available) {
            $paid = min((float) $installment->amount, max(0, $available));
            $available -= $paid;
            $remaining = (float) $installment->amount - $paid;

            return [
                'installment' => $installment,
                'paid' => $paid,
                'remaining' => $remaining,
                'status' => $remaining <= 0 ? 'lunas' : ($paid > 0 ? 'sebagian' : 'belum'),
                'overdue' => $remaining > 0 && $installment->due_date->lt(today()),
            ];
        })->values();
    }

    /**
     * Tagihan berikutnya: sisa semua angsuran pada tanggal jatuh tempo belum lunas yang paling awal.
     *
     * @return array{due_date: \Illuminate\Support\Carbon, amount: float}|null
     */
    public function nextInstallmentDue(): ?array
    {
        $unpaid = $this->installmentSchedule()->filter(fn ($row) => $row['remaining'] > 0);
        $first = $unpaid->first();

        if (! $first) {
            return null;
        }

        $dueDate = $first['installment']->due_date;

        return [
            'due_date' => $dueDate,
            'amount' => (float) $unpaid->filter(fn ($row) => $row['installment']->due_date->equalTo($dueDate))->sum('remaining'),
        ];
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