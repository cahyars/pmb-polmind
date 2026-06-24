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

Route::get('/', function () {
    return view('public.home');
});

Route::get('/login', function () {
    return view('auth.login');
});

Route::get('/register', function () {
    return view('auth.register');
});

Route::prefix('camaba')->name('camaba.')->group(function () {
    Route::get('/dashboard', function () {
        return view('camaba.dashboard');
    })->name('dashboard');

    Route::get('/biodata', function () {
        return view('camaba.biodata.index');
    })->name('biodata');

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

    Route::get('/master-data', function () {
        return view('admin.master-data.index');
    })->name('master-data.index');

    Route::get('/integrations', [IntegrationController::class, 'index'])->name('integrations.index');
    Route::patch('/integrations/{applicant}/processing', [IntegrationController::class, 'markProcessing'])->name('integrations.processing');
    Route::patch('/integrations/{applicant}/synced', [IntegrationController::class, 'markSynced'])->name('integrations.synced');
    Route::patch('/integrations/{applicant}/failed', [IntegrationController::class, 'markFailed'])->name('integrations.failed');
});