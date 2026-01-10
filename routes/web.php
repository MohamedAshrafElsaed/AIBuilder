<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Settings\SocialAccountController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// OAuth Routes
Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect'])
    ->whereIn('provider', ['github', 'google'])
    ->name('auth.social');

Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->whereIn('provider', ['github', 'google'])
    ->name('auth.social.callback');

Route::middleware(['auth'])->group(function () {
    Route::delete('/settings/social-accounts/{provider}', [SocialAccountController::class, 'destroy'])
        ->whereIn('provider', ['github', 'google'])
        ->name('settings.social-accounts.destroy');
});

require __DIR__ . '/settings.php';
