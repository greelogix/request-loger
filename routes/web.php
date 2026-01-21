<?php

use Gl\RequestLogger\Http\Controllers\LogViewerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::get('/request-logs', [LogViewerController::class, 'index'])->name('request-logger.index');
    Route::get('/request-logs/{id}', [LogViewerController::class, 'show'])->name('request-logger.show');
    Route::get('/request-logs-check-new', [LogViewerController::class, 'checkNew'])->name('request-logger.check-new');
    Route::delete('/request-logs', [LogViewerController::class, 'destroy'])->name('request-logger.destroy');
});
