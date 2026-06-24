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

Route::get('/camaba/dashboard', function () {
    return view('camaba.dashboard');
});

Route::get('/admin/dashboard', function () {
    return view('admin.dashboard');
});