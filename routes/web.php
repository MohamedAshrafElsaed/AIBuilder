<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GitHubController;
use App\Http\Controllers\ProjectController;
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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/github/connect', [GitHubController::class, 'connect'])->name('github.connect');
    Route::get('/github/callback', [GitHubController::class, 'callback'])->name('github.callback');

    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::get('/projects/confirm', [ProjectController::class, 'confirm'])->name('projects.confirm');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');

    Route::delete('/settings/social-accounts/{provider}', [SocialAccountController::class, 'destroy'])
        ->whereIn('provider', ['github', 'google'])
        ->name('settings.social-accounts.destroy');
});

require __DIR__ . '/settings.php';
