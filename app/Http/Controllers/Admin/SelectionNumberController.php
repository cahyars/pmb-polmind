<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionWave;
use App\Services\SelectionNumber;

/**
 * Master Data > Nomor Peserta Seleksi: memberi dan menyusun ulang nomor peserta per gelombang.
 */
class SelectionNumberController extends Controller
{
    public function assign(AdmissionWave $admissionWave)
    {
        $assigned = SelectionNumber::assignMissing($admissionWave);

        return $this->back($admissionWave, $assigned
            ? $assigned . ' camaba ' . $admissionWave->name . ' mendapat nomor peserta baru.'
            : 'Semua camaba ' . $admissionWave->name . ' yang sudah bayar formulir sudah bernomor.');
    }

    public function renumber(AdmissionWave $admissionWave)
    {
        $total = SelectionNumber::renumber($admissionWave);

        return $this->back($admissionWave, 'Nomor peserta ' . $admissionWave->name . ' disusun ulang: ' . $total . ' peserta, mulai '
            . (SelectionNumber::format($admissionWave, 1)) . '.');
    }

    private function back(AdmissionWave $admissionWave, string $message)
    {
        return redirect()
            ->route('admin.master-data.show', ['nomor-peserta', 'gelombang' => $admissionWave->id])
            ->with('success', $message);
    }
}
