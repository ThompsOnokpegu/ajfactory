<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Http\Middleware\CheckEnrollment;
use App\Http\Controllers\VaultController;

/*
|--------------------------------------------------------------------------
| Public Marketing & Sales Routes
|--------------------------------------------------------------------------
*/

// Agency Landing Page (Business Clients)
Route::get('/', function () {
    return view('welcome');
});

// Builders Landing Page (TikTok Waitlist)
Route::get('/builders', function () {
    return view('builders');
});

// Accelerator Sales Page
Route::get('/accelerator', function () {
    return view('accelerator');
});

// Dedicated Checkout Page
Route::get('/checkout', function () {
    return view('checkout');
});

// Thank You / Verification Page
Volt::route('/thank-you', 'thank-you');

/*
|--------------------------------------------------------------------------
| Authenticated Member Routes
|--------------------------------------------------------------------------
*/

// 1. The "Home" Fix: Redirects any default Laravel 'home' calls to dashboard
Route::get('/home', function () {
    return redirect()->route('dashboard');
})->middleware(['auth'])->name('home');

// 2. Protected Member Terminal
Route::middleware(['auth', CheckEnrollment::class])->group(function () {
    
    // The Main Member Dashboard
    Volt::route('/dashboard', 'dashboard.terminal')->name('dashboard');

    // Secure Snapshot Vault Downloads
    Route::get('/vault/download/{lessonId}', [VaultController::class, 'download'])
        ->name('vault.download');

});