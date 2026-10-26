<?php

namespace App\Http\Controllers\Camaba;

use App\Http\Controllers\Controller;
use App\Models\ApplicantDocument;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Berkas camaba disimpan privat; hanya pemiliknya yang dapat membuka.
 */
class FileController extends Controller
{
    public function document(ApplicantDocument $document)
    {
        return $this->stream($document->applicant_id, $document->file_path);
    }

    public function paymentProof(Payment $payment)
    {
        return $this->stream($payment->applicant_id, $payment->proof_file_path);
    }

    private function stream(int $ownerId, ?string $path)
    {
        abort_unless($ownerId === Auth::guard('applicant')->id(), 404);
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
