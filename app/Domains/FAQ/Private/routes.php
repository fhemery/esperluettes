<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\FAQ\Private\Controllers\Admin\FaqCategoryController;
use App\Domains\FAQ\Private\Controllers\Admin\FaqQuestionController;
use App\Domains\FAQ\Private\Controllers\FaqController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])
    ->group(function () {
        Route::get('/faq', [FaqController::class, 'index'])->name('faq.index');
        Route::get('/faq/{categorySlug}', [FaqController::class, 'index'])->name('faq.category');
    });

Route::middleware(['web', 'auth', 'role:' . Roles::ADMIN . ',' . Roles::TECH_ADMIN])
    ->prefix('admin/faq')
    ->name('faq.admin.')
    ->group(function () {
        Route::put('faq-categories/reorder', [FaqCategoryController::class, 'reorder'])->name('faq-categories.reorder');
        Route::post('faq-categories/{faqCategory}/toggle-active', [FaqCategoryController::class, 'toggleActive'])->name('faq-categories.toggle-active');
        Route::resource('faq-categories', FaqCategoryController::class)->except(['show']);

        Route::put('faq-questions/reorder', [FaqQuestionController::class, 'reorder'])->name('faq-questions.reorder');
        Route::post('faq-questions/{faqQuestion}/toggle-active', [FaqQuestionController::class, 'toggleActive'])->name('faq-questions.toggle-active');
        Route::resource('faq-questions', FaqQuestionController::class)->except(['show']);
    });
