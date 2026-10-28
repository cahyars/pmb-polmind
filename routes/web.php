<?php

use App\Http\Controllers\BrochureController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FileController as AdminFileController;
use App\Http\Controllers\Admin\ApplicantController;
use App\Http\Controllers\Admin\BrochureController as AdminBrochureController;
use App\Http\Controllers\Admin\DocumentVerificationController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\PaymentVerificationController;
use App\Http\Controllers\Admin\SelectionController;
use App\Http\Controllers\Admin\ReRegistrationController;
use App\Http\Controllers\Admin\IntegrationController;
use App\Http\Controllers\Admin\FollowUpController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\SelectionScheduleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\CamabaAuthController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\Camaba\DashboardController as CamabaDashboardController;
use App\Http\Controllers\Camaba\BiodataController as CamabaBiodataController;
use App\Http\Controllers\Camaba\DocumentController as CamabaDocumentController;
use App\Http\Controllers\Camaba\FileController as CamabaFileController;
use App\Http\Controllers\Camaba\PaymentController as CamabaPaymentController;
use App\Http\Controllers\Camaba\PrintController as CamabaPrintController;
use App\Http\Controllers\Camaba\SelectionStatusController as CamabaSelectionStatusController;
use App\Http\Controllers\Camaba\ReRegistrationController as CamabaReRegistrationController;
use App\Http\Middleware\AdminAccess;
use App\Http\Middleware\EnsureRegistrationPaid;
use App\Http\Middleware\SelectAdminPmbYear;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/brosur/{brochure}', [BrochureController::class, 'download'])
    ->middleware('throttle:60,1')
    ->name('brochures.download');

// Terbuka untuk tamu karena sekolah asal dipilih saat registrasi.
Route::get('/referensi/sekolah', [SchoolController::class, 'index'])
    ->middleware('throttle:60,1')
    ->name('schools.index');

Route::get('/referensi/wilayah', [RegionController::class, 'index'])
    ->middleware('throttle:120,1')
    ->name('regions.index');

Route::get('/login', [CamabaAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [CamabaAuthController::class, 'login'])
    ->middleware('throttle:15,1')
    ->name('login.store');

Route::get('/register', [CamabaAuthController::class, 'showRegister'])->name('register');
Route::post('/register', [CamabaAuthController::class, 'register'])
    ->middleware('throttle:20,1')
    ->name('register.store');

Route::post('/logout', [CamabaAuthController::class, 'logout'])->name('logout');

Route::middleware('auth:applicant')
        ->prefix('camaba')
        ->name('camaba.')
        ->group(function () {
        Route::get('/dashboard', [CamabaDashboardController::class, 'index'])->name('dashboard');

        Route::get('/pembayaran', [CamabaPaymentController::class, 'index'])->name('payments.index');
        Route::post('/pembayaran/{invoice}/upload-proof', [CamabaPaymentController::class, 'uploadProof'])
            ->middleware('throttle:15,1')
            ->name('payments.upload-proof');
        Route::get('/pembayaran/bukti/{payment}', [CamabaFileController::class, 'paymentProof'])->name('payments.proof');

        Route::get('/cetak/invoice/{invoice}', [CamabaPrintController::class, 'invoice'])->name('print.invoice');
        Route::get('/cetak/kwitansi/{payment}', [CamabaPrintController::class, 'receipt'])->name('print.receipt');

        Route::get('/upload-berkas/{document}/file', [CamabaFileController::class, 'document'])->name('documents.file');

        // Terbuka setelah pembayaran formulir diverifikasi admin.
        Route::middleware(EnsureRegistrationPaid::class)->group(function () {
            Route::get('/biodata', [CamabaBiodataController::class, 'edit'])->name('biodata.edit');
            Route::put('/biodata', [CamabaBiodataController::class, 'update'])->name('biodata.update');

            Route::get('/upload-berkas', [CamabaDocumentController::class, 'index'])->name('documents.index');
            Route::post('/upload-berkas', [CamabaDocumentController::class, 'store'])
                ->middleware('throttle:30,1')
                ->name('documents.store');
            Route::delete('/upload-berkas/{document}', [CamabaDocumentController::class, 'destroy'])->name('documents.destroy');

            Route::get('/status-seleksi', [CamabaSelectionStatusController::class, 'index'])->name('status-seleksi');
            Route::get('/pengumuman', [CamabaSelectionStatusController::class, 'announcement'])->name('pengumuman');

            Route::get('/daftar-ulang', [CamabaReRegistrationController::class, 'index'])->name('re-registration.index');

            Route::get('/cetak/kartu-ujian', [CamabaPrintController::class, 'examCard'])->name('print.exam-card');
        });
    });

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:web')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])
            ->middleware('throttle:10,1')
            ->name('login.store');
    });
});

Route::prefix('admin')->name('admin.')->middleware(['auth:web', AdminAccess::class, SelectAdminPmbYear::class])->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
    Route::post('/pmb-year', [AdminAuthController::class, 'selectPmbYear'])->name('pmb-year.select');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/applicants', [ApplicantController::class, 'index'])->name('applicants.index');

    Route::get('/applicants/{registration_number}', [ApplicantController::class, 'show'])->name('applicants.show');
    Route::delete('/applicants/{registration_number}', [ApplicantController::class, 'destroy'])->name('applicants.destroy');
    Route::patch('/applicants/{registration_number}/waive-registration-fee', [ApplicantController::class, 'waiveRegistrationFee'])->name('applicants.waive-registration-fee');

    Route::get('/documents', [DocumentVerificationController::class, 'index'])->name('documents.index');
    Route::get('/documents/{document}/file', [AdminFileController::class, 'document'])->name('documents.file');
    Route::get('/payments/{payment}/proof', [AdminFileController::class, 'paymentProof'])->name('payments.proof');
    Route::patch('/documents/{document}/accept', [DocumentVerificationController::class, 'accept'])->name('documents.accept');
    Route::patch('/documents/{document}/reject', [DocumentVerificationController::class, 'reject'])->name('documents.reject');

    // Hanya Admin PMB: tujuan pembayaran.
    Route::middleware(AdminAccess::class . ':admin')->group(function () {
        Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
        Route::post('/payment-methods', [PaymentMethodController::class, 'store'])->name('payment-methods.store');
        Route::put('/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'update'])->name('payment-methods.update');
        Route::patch('/payment-methods/{paymentMethod}/toggle', [PaymentMethodController::class, 'toggle'])->name('payment-methods.toggle');
        Route::delete('/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');
    });

    Route::get('/payments', [PaymentVerificationController::class, 'index'])->name('payments.index');
    Route::patch('/payments/{payment}/accept', [PaymentVerificationController::class, 'accept'])->name('payments.accept');
    Route::patch('/payments/{payment}/reject', [PaymentVerificationController::class, 'reject'])->name('payments.reject');

    Route::get('/selections', [SelectionController::class, 'index'])->name('selections.index');
    Route::patch('/selections/{applicant}/accept', [SelectionController::class, 'accept'])->name('selections.accept');
    Route::patch('/selections/{applicant}/reserve', [SelectionController::class, 'reserve'])->name('selections.reserve');
    Route::patch('/selections/{applicant}/reject', [SelectionController::class, 'reject'])->name('selections.reject');

    Route::get('/re-registrations', [ReRegistrationController::class, 'index'])->name('re-registrations.index');
    Route::post('/re-registrations/generate-nim', [ReRegistrationController::class, 'generateNim'])->name('re-registrations.generate-nim');
    Route::patch('/re-registrations/{reRegistration}/validate', [ReRegistrationController::class, 'validateReRegistration'])->name('re-registrations.validate');
    Route::patch('/re-registrations/{reRegistration}/reject', [ReRegistrationController::class, 'reject'])->name('re-registrations.reject');
    Route::patch('/re-registrations/{reRegistration}/ready-sync', [ReRegistrationController::class, 'markReadySync'])->name('re-registrations.ready-sync');

    Route::get('/follow-ups', [FollowUpController::class, 'index'])->name('follow-ups.index');
    Route::post('/follow-ups/{applicant}', [FollowUpController::class, 'store'])->name('follow-ups.store');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/applicants', [ReportController::class, 'exportApplicants'])
        ->name('reports.export-applicants');
    Route::get('/reports/{section}', [ReportController::class, 'show'])
        ->whereIn('section', array_keys(ReportController::SECTIONS))
        ->name('reports.show');

    // Hanya Admin PMB: Master Data dan kelola akun.
    Route::middleware(AdminAccess::class . ':admin')->group(function () {
        Route::get('/master-data', [MasterDataController::class, 'index'])->name('master-data.index');
        Route::get('/master-data/{section}', [MasterDataController::class, 'show'])
            ->whereIn('section', array_keys(MasterDataController::SECTIONS))
            ->name('master-data.show');

        Route::post('/master-data/selection-components', [SelectionScheduleController::class, 'storeComponent'])->name('master-data.selection-components.store');
        Route::put('/master-data/selection-components/{selectionComponent}', [SelectionScheduleController::class, 'updateComponent'])->name('master-data.selection-components.update');
        Route::patch('/master-data/selection-components/{selectionComponent}/toggle', [SelectionScheduleController::class, 'toggleComponent'])->name('master-data.selection-components.toggle');

        Route::post('/master-data/selection-schedules', [SelectionScheduleController::class, 'storeSchedule'])->name('master-data.selection-schedules.store');
        Route::put('/master-data/selection-schedules/{selectionSchedule}', [SelectionScheduleController::class, 'updateSchedule'])->name('master-data.selection-schedules.update');
        Route::delete('/master-data/selection-schedules/{selectionSchedule}', [SelectionScheduleController::class, 'destroySchedule'])->name('master-data.selection-schedules.destroy');
        Route::put('/master-data/admission-waves/{admissionWave}/timeline', [SelectionScheduleController::class, 'updateTimeline'])->name('master-data.admission-waves.timeline');
        Route::put('/master-data/admission-waves/{admissionWave}/pics', [SelectionScheduleController::class, 'updatePics'])->name('master-data.admission-waves.pics');

        Route::put('/master-data/numbering', [MasterDataController::class, 'updateNumbering'])->name('master-data.numbering.update');
        Route::put('/master-data/numbering/receipt', [MasterDataController::class, 'updateReceiptNumbering'])->name('master-data.numbering.receipt');

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

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
    });

    Route::get('/brochures', [AdminBrochureController::class, 'index'])->name('brochures.index');
    Route::post('/brochures', [AdminBrochureController::class, 'store'])->middleware('throttle:20,1')->name('brochures.store');
    Route::put('/brochures/{brochure}', [AdminBrochureController::class, 'update'])->middleware('throttle:20,1')->name('brochures.update');
    Route::patch('/brochures/{brochure}/toggle', [AdminBrochureController::class, 'toggle'])->name('brochures.toggle');
    Route::delete('/brochures/{brochure}', [AdminBrochureController::class, 'destroy'])->name('brochures.destroy');
    Route::get('/brochures/{brochure}/file', [AdminBrochureController::class, 'file'])->name('brochures.file');

    Route::get('/integrations', [IntegrationController::class, 'index'])->name('integrations.index');
    Route::patch('/integrations/{applicant}/processing', [IntegrationController::class, 'markProcessing'])->name('integrations.processing');
    Route::patch('/integrations/{applicant}/synced', [IntegrationController::class, 'markSynced'])->name('integrations.synced');
    Route::patch('/integrations/{applicant}/failed', [IntegrationController::class, 'markFailed'])->name('integrations.failed');
});