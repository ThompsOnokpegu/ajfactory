<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Http\Middleware\CheckEnrollment;
use App\Http\Controllers\VaultController;
use App\Http\Controllers\TaabLeadController;
use App\Http\Controllers\MasterclassController;

/*
|--------------------------------------------------------------------------
| Public Marketing & Sales Routes
|--------------------------------------------------------------------------
*/
Route::get('/onboarding', function () {
    return view('onboarding');
})->name('onboarding'); // Create this view and wrap the component inside it

Route::get('/links', function () {
    return view('links');
})->name('links');

Route::get('/resume', function () {
    return view('resume');
})->name('resume');

// Agency Landing Page (Business Clients)
Route::get('/', function () {
    return view('welcome');
});

//Legal: terms and conditions
Route::get('/terms', function () {
    return view('legal');
})->name('legal');

// Builders Landing Page (TikTok Waitlist)
Route::get('/builders', function () {
    return view('builders');
})->name('builders');

// Accelerator Sales Page
Route::get('/accelerator', function () {
    return view('accelerator');
})->name('accelerator');

// Dedicated Checkout Page
Route::get('/checkout', function () {
    return view('checkout');
})->name('checkout');

// TAAB — The AI Automation Bootcamp (hub + masterclass registration)
Route::view('/taab', 'taab.index')->name('taab.index');
Route::post('/taab/register', [MasterclassController::class, 'register'])->name('taab.register');
Route::post('/taab/waitlist', [MasterclassController::class, 'waitlist'])->name('taab.waitlist');

// TAAB lead-magnet tools (top of funnel)
Route::view('/taab/scorecard', 'taab.scorecard')->name('taab.scorecard');
Route::view('/taab/roi-calculator', 'taab.roi-calculator')->name('taab.roi');
Route::view('/taab/tool-stack', 'taab.tool-stack-guide')->name('taab.tools');
Route::post('/taab/lead', [TaabLeadController::class, 'store'])->name('taab.lead.store');

// Installment balance — minimal signed pay page (link sent via n8n; no expiry)
Volt::route('/installment/{enrollment}/pay', 'installment-pay')
    ->name('installment.pay')
    ->middleware('signed');

// Thank You / Verification Page
Volt::route('/thank-you', 'thank-you')->name('thank-you');

Volt::route('/booking', 'booking-form');

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

/*
|--------------------------------------------------------------------------
| Admin Dashboard (auth + is_admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Volt::route('/', 'admin.overview')->name('admin.overview');
    Volt::route('/enrollments', 'admin.enrollments')->name('admin.enrollments');
    Volt::route('/checkpoints', 'admin.checkpoints')->name('admin.checkpoints');
    Volt::route('/masterclass', 'admin.masterclass')->name('admin.masterclass');
    Volt::route('/leads', 'admin.leads')->name('admin.leads');

    Route::get('/masterclass/export', [\App\Http\Controllers\Admin\ExportController::class, 'masterclass'])->name('admin.masterclass.export');
    Route::get('/leads/export', [\App\Http\Controllers\Admin\ExportController::class, 'leads'])->name('admin.leads.export');
});

/*
|--------------------------------------------------------------------------
| Account Settings (profile, password, appearance, 2FA)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');
    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware('password.confirm')
        ->name('two-factor.show');
});