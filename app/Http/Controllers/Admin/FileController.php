<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicantDocument;
use App\Models\Payment;
use Illuminate\Support\Facades\Storage;

/**
 * Membuka berkas camaba dan bukti pembayaran dari penyimpanan privat.
 */
class FileController extends Controller
{
    public function document(ApplicantDocument $document)
    {
        return $this->stream($document->file_path);
    }

    public function paymentProof(Payment $payment)
    {
        return $this->stream($payment->proof_file_path);
    }

    private function stream(?string $path)
    {
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
