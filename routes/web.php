<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ApplicantController;
use App\Http\Controllers\Admin\DocumentVerificationController;

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

    Route::get('/payments', function () {
        return view('admin.payments.index');
    })->name('payments.index');

    Route::get('/selections', function () {
        return view('admin.selections.index');
    })->name('selections.index');

    Route::get('/re-registrations', function () {
        return view('admin.re-registrations.index');
    })->name('re-registrations.index');

    Route::get('/follow-ups', function () {
        return view('admin.follow-ups.index');
    })->name('follow-ups.index');

    Route::get('/reports', function () {
        return view('admin.reports.index');
    })->name('reports.index');

    Route::get('/master-data', function () {
        return view('admin.master-data.index');
    })->name('master-data.index');

    Route::get('/integrations', function () {
        return view('admin.integrations.index');
    })->name('integrations.index');
});