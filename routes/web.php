<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PortfolioController;
use Illuminate\Support\Facades\Route;

Route::get('/', PortfolioController::class)->name('home');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

// Dashboard auth. The route is named "login" so Laravel's auth middleware redirects here.
Route::get('/admin/login', [Admin\AuthController::class, 'create'])->name('login');
Route::post('/admin/login', [Admin\AuthController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('login.store');

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::post('/logout', [Admin\AuthController::class, 'destroy'])->name('logout');

    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('/profile', [Admin\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [Admin\ProfileController::class, 'update'])->name('profile.update');

    Route::get('/account', [Admin\AccountController::class, 'edit'])->name('account.edit');
    Route::put('/account', [Admin\AccountController::class, 'update'])->name('account.update');

    Route::resource('stats', Admin\StatController::class)->except('show');
    Route::resource('experiences', Admin\ExperienceController::class)->except('show');
    Route::resource('projects', Admin\ProjectController::class)->except('show');
    Route::resource('skills', Admin\SkillController::class)->except('show');
    Route::resource('certifications', Admin\CertificationController::class)->except('show');
    Route::resource('educations', Admin\EducationController::class)->except('show');
    Route::resource('languages', Admin\LanguageController::class)->except('show');

    Route::resource('messages', Admin\MessageController::class)->only(['index', 'show', 'destroy']);
});
