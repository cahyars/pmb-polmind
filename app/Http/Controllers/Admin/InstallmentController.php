<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\InvoiceStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Skema cicilan daftar ulang untuk camaba tertentu. Diatur admin atas dasar
 * pengajuan di luar sistem; camaba lain tidak melihat opsi ini.
 */
class InstallmentController extends Controller
{
    /**
     * Pilihan skema cicilan (persen per angsuran) yang mengisi form; nominal tetap bisa diubah admin.
     * Default tanpa skema: dibayar sekaligus.
     */
    public const SCHEMES = [
        '50:50' => [50, 50],
        '50:25:25' => [50, 25, 25],
        '25:25:25:25' => [25, 25, 25, 25],
    ];

    /**
     * Baris awal form per item invoice: jadwal tersimpan, atau satu kali bayar penuh.
     *
     * @return Collection<int, array{item: InvoiceItem, installable: bool, rows: array}>
     */
    public static function formRows(Invoice $invoice): Collection
    {
        $dueDate = ($invoice->due_date ?? today())->toDateString();

        return $invoice->items->sortBy('sort_order')->values()->map(function (InvoiceItem $item) use ($invoice, $dueDate) {
            $saved = $invoice->hasInstallments() ? $invoice->installments->where('fee_component_id', $item->fee_component_id) : collect();

            $rows = $saved->isNotEmpty()
                ? $saved->map(fn ($row) => ['amount' => (float) $row->amount, 'due_date' => $row->due_date->toDateString()])
                : collect([['amount' => (float) $item->subtotal, 'due_date' => $dueDate]]);

            return [
                'item' => $item,
                'installable' => (bool) $item->feeComponent?->is_installable,
                'rows' => $rows->values()->all(),
            ];
        });
    }

    public function update(Request $request, string $registration_number)
    {
        [$applicant, $invoice] = $this->resolve($registration_number);

        if ($error = $this->guardEditable($invoice)) {
            return back()->withErrors($error);
        }

        $installableItems = $invoice->items->filter(fn (InvoiceItem $item) => $item->feeComponent?->is_installable);

        if ($installableItems->isEmpty()) {
            return back()->withErrors('Tidak ada komponen daftar ulang yang ditandai "Bisa dicicil" di Master Data Komponen Biaya.');
        }

        $validated = $request->validate([
            'installments' => ['required', 'array'],
            'installments.*' => ['array'],
            'installments.*.*.amount' => ['required', 'numeric', 'min:1'],
            'installments.*.*.due_date' => ['required', 'date'],
            'installment_note' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'installments.*.*.amount' => 'nominal angsuran',
            'installments.*.*.due_date' => 'tanggal jatuh tempo',
            'installment_note' => 'catatan',
        ]);

        $errors = [];
        $schedule = collect();

        foreach ($installableItems as $item) {
            $rows = collect($validated['installments'][$item->id] ?? [])->sortBy('due_date')->values();

            if ($rows->isEmpty()) {
                $errors[] = 'Isi minimal satu angsuran untuk ' . $item->name . '.';

                continue;
            }

            if (round($rows->sum('amount'), 2) !== round((float) $item->subtotal, 2)) {
                $errors[] = 'Total angsuran ' . $item->name . ' harus sama dengan tagihannya: Rp' . number_format($item->subtotal, 0, ',', '.') . '.';
            }

            foreach ($rows as $index => $row) {
                $schedule->push([
                    'fee_component_id' => $item->fee_component_id,
                    'name' => $item->name,
                    'sequence' => $index + 1,
                    'amount' => $row['amount'],
                    'due_date' => $row['due_date'],
                ]);
            }
        }

        if ($errors) {
            return back()->withErrors($errors)->withInput();
        }

        // Komponen yang tidak dicicil dibayar penuh bersama angsuran pertama.
        $firstDueDate = $schedule->where('sequence', 1)->min('due_date');

        foreach ($invoice->items->diff($installableItems) as $item) {
            $schedule->push([
                'fee_component_id' => $item->fee_component_id,
                'name' => $item->name,
                'sequence' => 1,
                'amount' => $item->subtotal,
                'due_date' => $firstDueDate,
            ]);
        }

        $firstAmount = (float) $schedule->where('sequence', 1)->sum('amount');
        $validPaid = $invoice->validPaidAmount();

        if ($applicant->re_registration_status === 'daftar_ulang_valid' && $firstAmount > $validPaid) {
            return back()->withErrors('Daftar ulang camaba sudah valid, sehingga total angsuran ke-1 tidak boleh melebihi pembayaran yang sudah masuk (Rp' . number_format($validPaid, 0, ',', '.') . ').')->withInput();
        }

        DB::transaction(function () use ($invoice, $schedule, $validated) {
            $invoice->installments()->delete();
            $invoice->installments()->createMany($schedule->all());

            $invoice->update([
                'installment_enabled_at' => $invoice->installment_enabled_at ?? now(),
                'installment_enabled_by_name' => $this->actorName(),
                'installment_note' => $validated['installment_note'] ?? null,
            ]);

            InvoiceStatus::refresh($invoice, $this->actorName());
        });

        return back()->with('success', 'Skema cicilan daftar ulang ' . $applicant->full_name . ' berhasil disimpan.');
    }

    public function destroy(string $registration_number)
    {
        [$applicant, $invoice] = $this->resolve($registration_number);

        if (! $invoice->hasInstallments()) {
            return back()->withErrors('Camaba ini tidak memiliki skema cicilan.');
        }

        if ($error = $this->guardEditable($invoice)) {
            return back()->withErrors($error);
        }

        if ($applicant->re_registration_status === 'daftar_ulang_valid') {
            return back()->withErrors('Cicilan tidak dapat dinonaktifkan karena daftar ulang sudah valid dengan skema cicilan. Ubah jadwal angsurannya saja.');
        }

        DB::transaction(function () use ($invoice) {
            $invoice->installments()->delete();

            $invoice->update([
                'installment_enabled_at' => null,
                'installment_enabled_by_name' => null,
                'installment_note' => null,
            ]);

            InvoiceStatus::refresh($invoice, $this->actorName());
        });

        return back()->with('success', 'Skema cicilan ' . $applicant->full_name . ' dinonaktifkan. Tagihan daftar ulang kembali dibayar sekaligus.');
    }

    /**
     * @return array{0: Applicant, 1: Invoice}
     */
    private function resolve(string $registrationNumber): array
    {
        $applicant = Applicant::where('registration_number', $registrationNumber)->firstOrFail();

        $invoice = $applicant->reRegistrationInvoice()->with(['items.feeComponent', 'installments', 'payments'])->first();

        abort_unless($invoice, 404);

        return [$applicant, $invoice];
    }

    private function guardEditable(Invoice $invoice): ?string
    {
        if ($invoice->status === 'paid') {
            return 'Tagihan daftar ulang sudah lunas, skema cicilan tidak perlu diubah.';
        }

        if ($invoice->payments->contains('status', 'waiting_verification')) {
            return 'Masih ada pembayaran daftar ulang yang menunggu verifikasi. Verifikasi dulu sebelum mengubah skema cicilan.';
        }

        return null;
    }
}
