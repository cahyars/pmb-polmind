<?php

namespace App\Http\Controllers\Camaba;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $applicant = Auth::guard('applicant')->user();

        $applicant->load([
            'pmbYear',
            'admissionWave',
            'studyProgram',
            'secondStudyProgram',
            'classType',
            'address',
            'education',
            'parentData',
            'documents.documentType',
            'registrationInvoice.items',
            'registrationInvoice.payments',
            'registrationInvoice.latestPayment',
            'reRegistrationInvoice.items',
            'reRegistrationInvoice.payments',
            'reRegistrationInvoice.latestPayment',
            'selection',
            'reRegistration',
            'latestFollowUp',
        ]);

        $requiredDocumentTypes = DocumentType::where('is_active', true)
            ->where('is_required', true)
            ->orderBy('sort_order')
            ->get();

        $requiredDocumentCount = $requiredDocumentTypes->count();

        $acceptedRequiredDocumentCount = $applicant->documents
            ->filter(function ($document) {
                return $document->documentType?->is_required && $document->status === 'diterima';
            })
            ->count();

        $documentProgress = $requiredDocumentCount > 0
            ? round(($acceptedRequiredDocumentCount / $requiredDocumentCount) * 100)
            : 0;

        $steps = collect([
            [
                'label' => 'Registrasi Akun',
                'status' => true,
                'description' => 'Akun pendaftaran berhasil dibuat.',
            ],
            [
                'label' => 'Lengkapi Biodata',
                'status' => $applicant->registration_status === 'biodata_lengkap',
                'description' => 'Lengkapi data diri, alamat, pendidikan, dan orang tua.',
            ],
            [
                'label' => 'Upload Berkas',
                'status' => $applicant->document_status === 'valid',
                'description' => 'Upload dokumen persyaratan pendaftaran.',
            ],
            [
                'label' => 'Pembayaran Pendaftaran',
                'status' => $applicant->payment_status === 'valid',
                'description' => 'Lakukan pembayaran dan tunggu validasi admin.',
            ],
            [
                'label' => 'Seleksi',
                'status' => $applicant->selection_status === 'diterima',
                'description' => 'Menunggu pengumuman hasil seleksi.',
            ],
            [
                'label' => 'Daftar Ulang',
                'status' => $applicant->re_registration_status === 'daftar_ulang_valid',
                'description' => 'Dilakukan setelah dinyatakan diterima.',
            ],
            [
                'label' => 'Sinkron SIAKAD',
                'status' => $applicant->sync_status === 'sudah_sinkron',
                'description' => 'Data mahasiswa tersinkron dan NIM diterbitkan.',
            ],
        ]);

        $completedSteps = $steps->where('status', true)->count();

        $overallProgress = $steps->count() > 0
            ? round(($completedSteps / $steps->count()) * 100)
            : 0;

        $registrationInvoice = $applicant->registrationInvoice;
        $reRegistrationInvoice = $applicant->reRegistrationInvoice;

        return view('camaba.dashboard', compact(
            'applicant',
            'requiredDocumentCount',
            'acceptedRequiredDocumentCount',
            'documentProgress',
            'steps',
            'completedSteps',
            'overallProgress',
            'registrationInvoice',
            'reRegistrationInvoice'
        ));
    }
}