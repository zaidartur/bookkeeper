<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IpAddressController;
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

        Route::post('/save', [MaintenanceController::class, 'save'])->name('maintenance.save');
        Route::post('/update', [MaintenanceController::class, 'update'])->name('maintenance.update');
        Route::post('/delete', [MaintenanceController::class, 'delete'])->name('maintenance.delete');
    });

    Route::prefix('/trouble')->group(function() {
        Route::get('/', [TroubleController::class, 'view'])->name('trouble');

        Route::post('/save', [TroubleController::class, 'save'])->name('trouble.save');
        Route::post('/update', [TroubleController::class, 'update'])->name('trouble.update');
        Route::post('/confirm', [TroubleController::class, 'confirm'])->name('trouble.confirm');
        Route::post('/delete', [TroubleController::class, 'delete'])->name('trouble.delete');
    });

    Route::prefix('/network')->group(function() {
        Route::get('/', [IpAddressController::class, 'view'])->name('ip');
        Route::get('/testing', [IpAddressController::class, 'testing'])->name('ip.test');

        Route::post('/save', [IpAddressController::class, 'save'])->name('ip.save');
        Route::post('/update', [IpAddressController::class, 'update'])->name('ip.update');
        Route::post('/confirm', [IpAddressController::class, 'confirm'])->name('ip.confirm');
        Route::post('/delete', [IpAddressController::class, 'delete'])->name('ip.delete');
        Route::post('/graphic', [IpAddressController::class, 'monitoring'])->name('ip.monitoring');
    });

    Route::prefix('/report')->group(function() {
        Route::get('/trouble', [TroubleController::class, 'report'])->name('report.trouble');
        Route::get('/maintenance', [MaintenanceController::class, 'report'])->name('report.maintenance');
        Route::get('/guest', [DashboardController::class, 'report_guest'])->name('report.guest');
        // Route::post('/guest/import', [DashboardController::class, 'import_guest'])->name('report.guest.import');
    });

    Route::get('/bukutamu', [DashboardController::class, 'view_import'])->name('import.guest');
    Route::post('/bukutamu', [DashboardController::class, 'save_import'])->name('import.save');
});

Route::get('/buku-tamu', [DashboardController::class, 'guestbook'])->name('guestbook');
Route::post('/buku-tamu/save-form', [DashboardController::class, 'save_guest'])->name('guestbook.save');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
