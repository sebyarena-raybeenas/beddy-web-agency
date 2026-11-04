<?php

use App\Http\Controllers\AttachmentDownloadController;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

// Authenticated download of a private attachment (never a public URL).
Route::get('/allegati/{attachment}', AttachmentDownloadController::class)
    ->middleware(['web', Authenticate::class])
    ->name('attachments.download');
