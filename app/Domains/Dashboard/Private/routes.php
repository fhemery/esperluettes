<?php

use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard::index');
    })->middleware(['auth', 'verified'])->name('dashboard');
});
