<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\MonitoredSimController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [MonitoredSimController::class, 'index'])->name('dashboard');
    Route::post('/sims', [MonitoredSimController::class, 'store'])->name('sims.store');
    Route::patch('/sims/{sim}/mode', [MonitoredSimController::class, 'toggleMode'])->name('sims.toggle-mode');
    Route::delete('/sims/{sim}', [MonitoredSimController::class, 'destroy'])->name('sims.destroy');
});

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
