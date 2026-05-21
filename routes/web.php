<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminActiveBookingController;
use App\Http\Controllers\AdminBookingCancelController;
use App\Http\Controllers\AdminBookingApprovalController;
use App\Http\Controllers\AdminBookingUpdateController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LivingAreaManagerController;
use App\Http\Controllers\LivingAreaSettingsController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home', [
        'authPanel' => request('auth'),
    ]);
})->name('home');

Route::view('/approval-pending', 'auth.approval-pending')->name('approval.pending');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'approved'])
    ->name('dashboard');

Route::get('/admin', AdminController::class)
    ->middleware(['auth', 'approved'])
    ->name('admin.index');

Route::middleware(['auth', 'approved'])->group(function () {
    Route::post('/admin/bookings/active', AdminActiveBookingController::class)->name('admin.bookings.active.store');
    Route::patch('/admin/bookings/{bookingGroup}', AdminBookingUpdateController::class)->name('admin.bookings.update');
    Route::patch('/admin/bookings/{bookingGroup}/cancel', AdminBookingCancelController::class)->name('admin.bookings.cancel');
    Route::patch('/admin/bookings/{bookingGroup}/approve', AdminBookingApprovalController::class)->name('admin.bookings.approve');
    Route::patch('/admin/living-areas/{livingArea}', LivingAreaSettingsController::class)->name('admin.living-areas.update');
    Route::patch('/admin/living-areas/{livingArea}/managers/{user}', LivingAreaManagerController::class)->name('admin.living-areas.managers.update');

    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::patch('/bookings/{bookingGroup}/payment', [BookingController::class, 'updatePayment'])->name('bookings.payment.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
