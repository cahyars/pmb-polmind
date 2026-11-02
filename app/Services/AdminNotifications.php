<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\ApplicantDocument;
use App\Models\Payment;
use App\Models\User;

/**
 * Badge pekerjaan tertunda di panel admin (mengikuti Tahun Akademik terpilih), dipakai
 * layout admin dan polling lonceng agar angka diperbarui tanpa refresh.
 */
class AdminNotifications
{
    public const LABELS = [
        'applicants' => ['label' => 'camaba baru sejak terakhir dibuka', 'url' => '/admin/applicants'],
        'documents' => ['label' => 'berkas menunggu verifikasi', 'url' => '/admin/documents'],
        'payments' => ['label' => 'pembayaran menunggu verifikasi', 'url' => '/admin/payments'],
    ];

    /**
     * @return array{applicants: int, documents: int, payments: int}
     */
    public static function counts(?User $user): array
    {
        return [
            'applicants' => Applicant::query()
                ->when($user?->applicants_seen_at, fn ($query, $seenAt) => $query->where('created_at', '>', $seenAt))
                ->count(),
            'documents' => ApplicantDocument::where('status', 'menunggu_verifikasi')->count(),
            'payments' => Payment::where('status', 'waiting_verification')->count(),
        ];
    }

    /**
     * Penanda data terakhir yang sudah diketahui browser, untuk mendeteksi data baru.
     *
     * @return array{applicant: int, payment: int}
     */
    public static function cursor(): array
    {
        return [
            'applicant' => (int) Applicant::max('id'),
            'payment' => (int) Payment::max('id'),
        ];
    }

    /**
     * Pendaftar dan bukti bayar baru setelah penanda, untuk pop-up di panel admin.
     *
     * @return list<array{title: string, text: string, url: string}>
     */
    public static function eventsAfter(int $applicantId, int $paymentId): array
    {
        $applicants = Applicant::where('id', '>', $applicantId)->latest('id')->take(5)->get(['id', 'full_name', 'registration_number']);
        $payments = Payment::with('applicant:id,full_name')
            ->where('id', '>', $paymentId)
            ->where('status', 'waiting_verification')
            ->latest('id')
            ->take(5)
            ->get();

        return [
            ...$applicants->map(fn ($applicant) => [
                'title' => 'Pendaftar baru',
                'text' => $applicant->full_name . ' (' . $applicant->registration_number . ')',
                'url' => route('admin.applicants.show', $applicant->registration_number),
            ]),
            ...$payments->map(fn ($payment) => [
                'title' => 'Bukti bayar baru',
                'text' => ($payment->applicant?->full_name ?? '-') . ' mengunggah bukti bayar.',
                'url' => route('admin.payments.index'),
            ]),
        ];
    }
}
