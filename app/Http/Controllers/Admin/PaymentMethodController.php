<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $paymentMethods = PaymentMethod::query()
            ->withCount('payments')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.payment-methods.index', [
            'paymentMethods' => $paymentMethods,
            'types' => PaymentMethod::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        PaymentMethod::create($this->validated($request));

        return back()->with('success', 'Tujuan pembayaran berhasil ditambahkan.');
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $paymentMethod->update($this->validated($request));

        return back()->with('success', 'Tujuan pembayaran berhasil diperbarui.');
    }

    public function toggle(PaymentMethod $paymentMethod)
    {
        $paymentMethod->update(['is_active' => ! $paymentMethod->is_active]);

        return back()->with('success', $paymentMethod->is_active
            ? 'Tujuan pembayaran ditampilkan ke camaba.'
            : 'Tujuan pembayaran disembunyikan dari camaba.');
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        if ($paymentMethod->payments()->exists()) {
            return back()->withErrors('Tujuan pembayaran ini sudah dipakai pada pembayaran camaba. Nonaktifkan saja agar riwayatnya tetap tercatat.');
        }

        $paymentMethod->delete();

        return back()->with('success', 'Tujuan pembayaran berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(PaymentMethod::TYPES))],
            'provider' => ['required', 'string', 'max:100'],
            'account_number' => [
                Rule::requiredIf(in_array($request->input('type'), ['bank_transfer', 'virtual_account', 'ewallet'], true)),
                'nullable',
                'string',
                'max:50',
            ],
            'account_name' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'account_number.required' => 'Nomor rekening / nomor tujuan wajib diisi untuk jenis ini.',
        ], [
            'provider' => 'nama bank / penyedia',
            'account_number' => 'nomor rekening',
            'account_name' => 'atas nama',
            'instructions' => 'petunjuk',
        ]);

        return [
            'type' => $validated['type'],
            'provider' => $validated['provider'],
            'account_number' => filled($validated['account_number'] ?? null)
                ? preg_replace('/\s+/', '', $validated['account_number'])
                : null,
            'account_name' => $validated['account_name'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
