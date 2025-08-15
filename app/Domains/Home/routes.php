<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Home\Controllers\HomeController;

Route::get('/', [HomeController::class, 'index'])->name('home');
