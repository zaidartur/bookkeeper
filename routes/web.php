<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TroubleController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    // return Inertia::render('Welcome', [
    //     'canLogin' => Route::has('login'),
    //     'canRegister' => Route::has('register'),
    //     'laravelVersion' => Application::VERSION,
    //     'phpVersion' => PHP_VERSION,
    // ]);
    // return redirect('/');
    return Inertia::render('Landing');
});

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::prefix('/')->middleware('auth')->group(function() {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('/maintenance')->group(function() {
        Route::get('/', [MaintenanceController::class, 'view'])->name('maintenance');
        Route::get('/input-maintenance', [MaintenanceController::class, 'input_form'])->name('mtc.form');
    });

    Route::prefix('/trouble')->group(function() {
        Route::get('/', [TroubleController::class, 'view'])->name('trouble');
    });
});

Route::get('/buku-tamu', [DashboardController::class, 'guestbook'])->name('guestbook');
Route::post('/buku-tamu/save-form', [DashboardController::class, 'save_guest'])->name('guestbook.save');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
