<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu angsuran pada jadwal pembayaran invoice daftar ulang.
 */
class InvoiceInstallment extends Model
{
    use LogsActivity;

    protected string $activityLabel = 'Angsuran daftar ulang';

    protected $fillable = [
        'invoice_id',
        'fee_component_id',
        'name',
        'sequence',
        'amount',
        'due_date',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function feeComponent()
    {
        return $this->belongsTo(FeeComponent::class);
    }

    protected function activityParentApplicantId(): ?int
    {
        return $this->invoice?->applicant_id;
    }

    protected function activitySubjectLabel(): ?string
    {
        return $this->name;
    }
}
