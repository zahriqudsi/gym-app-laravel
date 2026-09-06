<?php

use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/admin'));

// Printable documents — staff only.
Route::middleware(['web', 'auth'])->prefix('documents')->name('documents.')->group(function () {
    Route::get('receipts/{payment}', [DocumentController::class, 'receipt'])->name('receipt');
    Route::get('invoices/{invoice}', [DocumentController::class, 'invoice'])->name('invoice');
});
