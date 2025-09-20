<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Home\Private\Controllers\HomeController;

Route::get('/', [HomeController::class, 'index'])->name('home');
