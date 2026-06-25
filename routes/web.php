<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ApplicantController;
use App\Http\Controllers\Admin\DocumentVerificationController;
use App\Http\Controllers\Admin\PaymentVerificationController;
use App\Http\Controllers\Admin\SelectionController;
use App\Http\Controllers\Admin\ReRegistrationController;
use App\Http\Controllers\Admin\IntegrationController;
use App\Http\Controllers\Admin\FollowUpController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Auth\CamabaAuthController;
use App\Http\Controllers\Camaba\DashboardController as CamabaDashboardController;
use App\Http\Controllers\Camaba\BiodataController as CamabaBiodataController;
use App\Http\Controllers\Camaba\DocumentController as CamabaDocumentController;
use App\Http\Controllers\Camaba\SelectionStatusController as CamabaSelectionStatusController;

Route::get('/', function () {
    return view('public.home');
});

Route::get('/login', [CamabaAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [CamabaAuthController::class, 'login'])->name('login.store');

Route::get('/register', [CamabaAuthController::class, 'showRegister'])->name('register');
Route::post('/register', [CamabaAuthController::class, 'register'])->name('register.store');

Route::post('/logout', [CamabaAuthController::class, 'logout'])->name('logout');

Route::middleware('auth:applicant')
        ->prefix('camaba')
        ->name('camaba.')
        ->group(function () {
        Route::get('/dashboard', [CamabaDashboardController::class, 'index'])->name('dashboard');

        Route::get('/biodata', [CamabaBiodataController::class, 'edit'])->name('biodata.edit');
        Route::put('/biodata', [CamabaBiodataController::class, 'update'])->name('biodata.update');

        Route::get('/upload-berkas', [CamabaDocumentController::class, 'index'])->name('documents.index');
        Route::post('/upload-berkas', [CamabaDocumentController::class, 'store'])->name('documents.store');
        Route::delete('/upload-berkas/{document}', [CamabaDocumentController::class, 'destroy'])->name('documents.destroy');

        Route::get('/pembayaran', [CamabaPaymentController::class, 'index'])->name('payments.index');
        Route::post('/pembayaran/{invoice}/upload-proof', [CamabaPaymentController::class, 'uploadProof'])->name('payments.upload-proof');
    });

Route::prefix('camaba')->name('camaba.')->group(function () {

    Route::get('/berkas', function () {
        return view('camaba.berkas');
    })->name('berkas');

    Route::get('/pembayaran', function () {
        return view('camaba.pembayaran');
    })->name('pembayaran');

    Route::get('/status-seleksi', function () {
        return view('camaba.status-seleksi');
    })->name('status-seleksi');

    Route::get('/pengumuman', function () {
        return view('camaba.pengumuman');
    })->name('pengumuman');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/applicants', [ApplicantController::class, 'index'])->name('applicants.index');

    Route::get('/applicants/{registration_number}', [ApplicantController::class, 'show'])->name('applicants.show');

    Route::get('/documents', [DocumentVerificationController::class, 'index'])->name('documents.index');
    Route::patch('/documents/{document}/accept', [DocumentVerificationController::class, 'accept'])->name('documents.accept');
    Route::patch('/documents/{document}/reject', [DocumentVerificationController::class, 'reject'])->name('documents.reject');

    Route::get('/payments', [PaymentVerificationController::class, 'index'])->name('payments.index');
    Route::patch('/payments/{payment}/accept', [PaymentVerificationController::class, 'accept'])->name('payments.accept');
    Route::patch('/payments/{payment}/reject', [PaymentVerificationController::class, 'reject'])->name('payments.reject');

    Route::get('/selections', [SelectionController::class, 'index'])->name('selections.index');
    Route::patch('/selections/{applicant}/accept', [SelectionController::class, 'accept'])->name('selections.accept');
    Route::patch('/selections/{applicant}/reserve', [SelectionController::class, 'reserve'])->name('selections.reserve');
    Route::patch('/selections/{applicant}/reject', [SelectionController::class, 'reject'])->name('selections.reject');

    Route::get('/re-registrations', [ReRegistrationController::class, 'index'])->name('re-registrations.index');
    Route::patch('/re-registrations/{reRegistration}/validate', [ReRegistrationController::class, 'validateReRegistration'])->name('re-registrations.validate');
    Route::patch('/re-registrations/{reRegistration}/reject', [ReRegistrationController::class, 'reject'])->name('re-registrations.reject');
    Route::patch('/re-registrations/{reRegistration}/ready-sync', [ReRegistrationController::class, 'markReadySync'])->name('re-registrations.ready-sync');

    Route::get('/follow-ups', [FollowUpController::class, 'index'])->name('follow-ups.index');
    Route::post('/follow-ups/{applicant}', [FollowUpController::class, 'store'])->name('follow-ups.store');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::get('/master-data', [MasterDataController::class, 'index'])->name('master-data.index');

    Route::post('/master-data/study-programs', [MasterDataController::class, 'storeStudyProgram'])->name('master-data.study-programs.store');
    Route::put('/master-data/study-programs/{studyProgram}', [MasterDataController::class, 'updateStudyProgram'])->name('master-data.study-programs.update');
    Route::patch('/master-data/study-programs/{studyProgram}/toggle', [MasterDataController::class, 'toggleStudyProgram'])->name('master-data.study-programs.toggle');

    Route::post('/master-data/fee-components', [MasterDataController::class, 'storeFeeComponent'])->name('master-data.fee-components.store');
    Route::put('/master-data/fee-components/{feeComponent}', [MasterDataController::class, 'updateFeeComponent'])->name('master-data.fee-components.update');
    Route::patch('/master-data/fee-components/{feeComponent}/toggle', [MasterDataController::class, 'toggleFeeComponent'])->name('master-data.fee-components.toggle');

    Route::post('/master-data/pmb-years', [MasterDataController::class, 'storePmbYear'])->name('master-data.pmb-years.store');
    Route::put('/master-data/pmb-years/{pmbYear}', [MasterDataController::class, 'updatePmbYear'])->name('master-data.pmb-years.update');
    Route::patch('/master-data/pmb-years/{pmbYear}/toggle', [MasterDataController::class, 'togglePmbYear'])->name('master-data.pmb-years.toggle');

    Route::post('/master-data/admission-waves', [MasterDataController::class, 'storeAdmissionWave'])->name('master-data.admission-waves.store');
    Route::put('/master-data/admission-waves/{admissionWave}', [MasterDataController::class, 'updateAdmissionWave'])->name('master-data.admission-waves.update');
    Route::patch('/master-data/admission-waves/{admissionWave}/toggle', [MasterDataController::class, 'toggleAdmissionWave'])->name('master-data.admission-waves.toggle');

    Route::post('/master-data/class-types', [MasterDataController::class, 'storeClassType'])->name('master-data.class-types.store');
    Route::put('/master-data/class-types/{classType}', [MasterDataController::class, 'updateClassType'])->name('master-data.class-types.update');
    Route::patch('/master-data/class-types/{classType}/toggle', [MasterDataController::class, 'toggleClassType'])->name('master-data.class-types.toggle');

    Route::post('/master-data/document-types', [MasterDataController::class, 'storeDocumentType'])->name('master-data.document-types.store');
    Route::put('/master-data/document-types/{documentType}', [MasterDataController::class, 'updateDocumentType'])->name('master-data.document-types.update');
    Route::patch('/master-data/document-types/{documentType}/toggle', [MasterDataController::class, 'toggleDocumentType'])->name('master-data.document-types.toggle');

    Route::get('/integrations', [IntegrationController::class, 'index'])->name('integrations.index');
    Route::patch('/integrations/{applicant}/processing', [IntegrationController::class, 'markProcessing'])->name('integrations.processing');
    Route::patch('/integrations/{applicant}/synced', [IntegrationController::class, 'markSynced'])->name('integrations.synced');
    Route::patch('/integrations/{applicant}/failed', [IntegrationController::class, 'markFailed'])->name('integrations.failed');
});