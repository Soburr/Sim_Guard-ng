<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\MonitoredSimController;
use App\Http\Controllers\WalletController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [MonitoredSimController::class, 'index'])->name('dashboard');
    Route::post('/sims', [MonitoredSimController::class, 'store'])->name('sims.store');
    Route::patch('/sims/{sim}/mode', [MonitoredSimController::class, 'toggleMode'])->name('sims.toggle-mode');
    Route::delete('/sims/{sim}', [MonitoredSimController::class, 'destroy'])->name('sims.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/wallet/fund', [WalletController::class, 'fund'])->name('wallet.fund');
    Route::get('/wallet/callback', [WalletController::class, 'callback'])->name('wallet.callback');
});

Route::post('/webhooks/paystack', [WalletController::class, 'webhook'])->name('webhooks.paystack');

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
