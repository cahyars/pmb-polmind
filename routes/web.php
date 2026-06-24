<?php

use Illuminate\Support\Facades\Route;

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
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    Route::get('/applicants', function () {
        return view('admin.applicants.index');
    })->name('applicants.index');

    Route::get('/applicants/{registration_number}', function ($registration_number) {
        return view('admin.applicants.show', compact('registration_number'));
    })->name('applicants.show');

    Route::get('/documents', function () {
        return view('admin.documents.index');
    })->name('documents.index');
});