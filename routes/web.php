<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/emails/product-update', [EmailController::class, 'productUpdateForm'])->name('emails.product.form');
Route::post('/emails/product-update', [EmailController::class, 'productUpdateGenerate'])->name('emails.product.generate');

Route::get('/history', [EmailController::class, 'history'])->name('history');
Route::get('/history/{id}', [EmailController::class, 'historyShow'])->name('history.show');
