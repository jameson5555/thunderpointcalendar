<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::view('/approval-pending', 'auth.approval-pending')->name('approval.pending');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'approved'])
    ->name('dashboard');

Route::get('/admin', AdminController::class)
    ->middleware(['auth', 'approved'])
    ->name('admin.index');

Route::middleware(['auth', 'approved'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
