<?php

declare(strict_types=1);

use App\Http\Controllers\Pixury\PixuryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'updateUserActivity'])
    ->prefix('dashboard/pixury')
    ->name('dashboard.user.pixury.')
    ->group(static function () {
        Route::get('/', [PixuryController::class, 'index'])->name('index');
        Route::get('/templates', [PixuryController::class, 'templates'])->name('templates');
        Route::get('/create', [PixuryController::class, 'create'])->name('create');
        Route::get('/creations', [PixuryController::class, 'creations'])->name('creations');

        // API endpoints for generation
        Route::post('/generate', [PixuryController::class, 'store'])->name('generate');
        Route::get('/creations/{id}', [PixuryController::class, 'show'])->name('creations.show');
        Route::get('/creations/{id}/status', [PixuryController::class, 'status'])->name('creations.status');
        Route::post('/creations/{id}/retry', [PixuryController::class, 'retry'])->name('creations.retry');

        // Product image upload
        Route::post('/upload', [PixuryController::class, 'upload'])->name('upload');
    });