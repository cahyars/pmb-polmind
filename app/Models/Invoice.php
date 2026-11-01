<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Invoice extends Model
{
    use HasFactory, LogsActivity;

    protected string $activityLabel = 'Invoice';

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
     * Saldo per komponen tagihan (mis. UKT dan SPI daftar ulang): total, terbayar, menunggu verifikasi,
     * sisa, dan nominal yang dibayar berikutnya (angsuran terdekat bila dicicil, selain itu sisa penuh).
     *
     * @return Collection<int, array{item: InvoiceItem, fee_component_id: ?int, total: float, paid: float, waiting: float, remaining: float, due: float, schedule: Collection, required_first: float, status: string}>
     */
    public function componentBalances(): Collection
    {
        $this->loadMissing(['items', 'installments', 'payments.allocations']);

        ['valid' => $valid, 'waiting' => $waiting] = $this->paidPerComponent();

        return $this->items->sortBy('sort_order')->values()->map(function (InvoiceItem $item) use ($valid, $waiting) {
            $key = (int) $item->fee_component_id;
            $total = (float) $item->subtotal;
            $paid = min($total, $valid[$key] ?? 0.0);
            $waitingAmount = (float) ($waiting[$key] ?? 0);

            $rows = $this->hasInstallments()
                ? $this->installments->where('fee_component_id', $item->fee_component_id)->sortBy([['sequence', 'asc'], ['due_date', 'asc']])->values()
                : collect();

            // Terbayar komponen ini mengisi angsurannya berurutan.
            $available = $paid;
            $schedule = $rows->map(function (InvoiceInstallment $installment) use (&$available) {
                $rowPaid = min((float) $installment->amount, max(0, $available));
                $available -= $rowPaid;
                $rowRemaining = (float) $installment->amount - $rowPaid;

                return [
                    'installment' => $installment,
                    'paid' => $rowPaid,
                    'remaining' => $rowRemaining,
                    'status' => $rowRemaining <= 0 ? 'lunas' : ($rowPaid > 0 ? 'sebagian' : 'belum'),
                    'overdue' => $rowRemaining > 0 && $installment->due_date->lt(today()),
                ];
            });

            $remaining = $total - $paid;
            $next = $schedule->first(fn ($row) => $row['remaining'] > 0);

            return [
                'item' => $item,
                'fee_component_id' => $item->fee_component_id,
                'total' => $total,
                'paid' => $paid,
                'waiting' => $waitingAmount,
                'remaining' => $remaining,
                'due' => $next ? $next['remaining'] : $remaining,
                'schedule' => $schedule,
                // Daftar ulang valid setelah angsuran ke-1 tiap komponen terbayar (tanpa cicilan: lunas).
                'required_first' => $rows->isNotEmpty() ? (float) $rows->where('sequence', 1)->sum('amount') : $total,
                'status' => match (true) {
                    $remaining <= 0 => 'paid',
                    $waitingAmount > 0 => 'waiting',
                    $paid > 0 => 'partial',
                    default => 'unpaid',
                },
            ];
        });
    }

    /**
     * Pembayaran valid dan yang menunggu verifikasi per komponen (kunci: fee_component_id).
     * Pembayaran lama tanpa rincian komponen dibagi berurutan: angsuran paling awal dulu,
     * atau urutan komponen pada invoice.
     *
     * @return array{valid: array<int, float>, waiting: array<int, float>}
     */
    private function paidPerComponent(): array
    {
        $valid = [];
        $waiting = [];
        $unallocated = 0.0;

        foreach ($this->payments as $payment) {
            if ($payment->allocations->isEmpty()) {
                $unallocated += $payment->status === 'valid' ? (float) $payment->amount : 0;

                continue;
            }

            foreach ($payment->allocations as $allocation) {
                $key = (int) $allocation->fee_component_id;

                if ($payment->status === 'valid') {
                    $valid[$key] = ($valid[$key] ?? 0) + (float) $allocation->amount;
                } elseif ($payment->status === 'waiting_verification') {
                    $waiting[$key] = ($waiting[$key] ?? 0) + (float) $allocation->amount;
                }
            }
        }

        if ($unallocated > 0) {
            $slots = $this->hasInstallments()
                ? $this->installments->sortBy([['sequence', 'asc'], ['due_date', 'asc']])->map(fn ($row) => [(int) $row->fee_component_id, (float) $row->amount])
                : $this->items->sortBy('sort_order')->map(fn ($item) => [(int) $item->fee_component_id, (float) $item->subtotal]);

            $covered = $valid;

            foreach ($slots as [$key, $amount]) {
                // Bagian slot yang sudah tertutup pembayaran berincian tidak diisi lagi.
                $already = min($amount, $covered[$key] ?? 0);
                $covered[$key] = ($covered[$key] ?? 0) - $already;
                $take = min($amount - $already, $unallocated);

                if ($take > 0) {
                    $valid[$key] = ($valid[$key] ?? 0) + $take;
                    $unallocated -= $take;
                }
            }
        }

        return ['valid' => $valid, 'waiting' => $waiting];
    }

    /**
     * Jadwal angsuran beserta statusnya; pembayaran tiap komponen mengisi angsuran komponen itu.
     *
     * @return Collection<int, array{installment: InvoiceInstallment, paid: float, remaining: float, status: string, overdue: bool}>
     */
    public function installmentSchedule(): Collection
    {
        return $this->componentBalances()
            ->flatMap(fn (array $balance) => $balance['schedule'])
            ->sortBy([
                fn ($a, $b) => $a['installment']->due_date <=> $b['installment']->due_date,
                fn ($a, $b) => $a['installment']->sequence <=> $b['installment']->sequence,
            ])
            ->values();
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

    protected function activitySubjectLabel(): ?string
    {
        return $this->invoice_number;
    }
}
