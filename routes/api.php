<?php

use App\Http\Controllers\Api\SiakadStudentController;
use App\Http\Middleware\SiakadApiToken;
use Illuminate\Support\Facades\Route;

// Ditarik oleh SIAKAD. Dokumentasi: docs/API_SIAKAD.md
Route::prefix('siakad')->name('api.siakad.')->middleware([SiakadApiToken::class, 'throttle:120,1'])->group(function () {
    Route::get('/mahasiswa', [SiakadStudentController::class, 'index'])->name('mahasiswa.index');
    Route::get('/mahasiswa/{applicant:registration_number}', [SiakadStudentController::class, 'show'])->name('mahasiswa.show');
    Route::post('/mahasiswa/{applicant:registration_number}/konfirmasi', [SiakadStudentController::class, 'confirm'])->name('mahasiswa.konfirmasi');
});
