<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Volt::route('dashboard', 'dashboard.index')->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    // Member Management Routes
    Route::prefix('members')->name('members.')->group(function () {
        Volt::route('/', 'members.index')->name('index');
        Volt::route('/create', 'members.create')->name('create');
        Volt::route('/complete-profile', 'members.complete-profile')->name('complete');
        Volt::route('/{member}', 'members.show')->name('show');
        Volt::route('/{member}/edit', 'members.edit')->name('edit');
    });

    // Dependent Management Routes
    Route::prefix('dependents')->name('dependents.')->group(function () {
        Volt::route('/manage/{member}', 'dependents.manage')->name('manage');
    });

    // Contribution Management Routes
    Route::prefix('contributions')->name('contributions.')->group(function () {
        Volt::route('/', 'contributions.index')->name('index');
        Volt::route('/create', 'contributions.create')->name('create');
        Volt::route('/submit', 'contributions.member-submit')->name('submit');
        Volt::route('/verify', 'contributions.verify')->name('verify');
        Volt::route('/{contribution}', 'contributions.show')->name('show');
        Volt::route('/{contribution}/edit', 'contributions.edit')->name('edit');

        // Receipt Routes
        Route::get('/{contribution}/receipt/download', [\App\Http\Controllers\ReceiptController::class, 'download'])
            ->name('receipt.download');
        Route::get('/{contribution}/receipt/view', [\App\Http\Controllers\ReceiptController::class, 'view'])
            ->name('receipt.view');
        Route::get('/{contribution}/receipt/print', [\App\Http\Controllers\ReceiptController::class, 'print'])
            ->name('receipt.print');
    });

    // Loan Management Routes
    Route::prefix('loans')->name('loans.')->group(function () {
        Volt::route('/', 'loans.index')->name('index');
        Volt::route('/create', 'loans.create')->name('create');
        Volt::route('/{loan}', 'loans.show')->name('show');
        Volt::route('/{loan}/edit', 'loans.edit')->name('edit');
    });

    // Health Claims Management Routes
    Route::prefix('health-claims')->name('health-claims.')->group(function () {
        Volt::route('/', 'health-claims.index')->name('index');
        Volt::route('/create', 'health-claims.create')->name('create');
        Volt::route('/{claim}', 'health-claims.show')->name('show');
        Volt::route('/{claim}/edit', 'health-claims.edit')->name('edit');
    });

    // Program Management Routes
    Route::prefix('programs')->name('programs.')->group(function () {
        Volt::route('/', 'programs.index')->name('index');
        Volt::route('/create', 'programs.create')->name('create');
        Volt::route('/{program}', 'programs.show')->name('show');
        Volt::route('/{program}/edit', 'programs.edit')->name('edit');
    });

    // Program Enrollments Routes
    Route::prefix('program-enrollments')->name('program-enrollments.')->group(function () {
        Volt::route('/', 'program-enrollments.index')->name('index');
    });

    // Cashout Routes
    Route::prefix('cashout')->name('cashout.')->group(function () {
        Volt::route('/', 'cashout-request.index')->name('index');
        Volt::route('/request', 'cashout-request.create')->name('create');
        Volt::route('/{request}', 'cashout-request.show')->name('show');
    });

    // Reports Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Volt::route('/', 'reports.index')->name('index');
    });

    // Admin Routes
    Route::prefix('admin')->name('admin.')->group(function () {
        // User Management
        Volt::route('users', 'admin.users.index')->name('users.index');
        Volt::route('users/create', 'admin.users.create')->name('users.create');
        Volt::route('users/{user}', 'admin.users.show')->name('users.show');
        Volt::route('users/{user}/edit', 'admin.users.edit')->name('users.edit');

        // Role Management
        Volt::route('roles', 'admin.roles.index')->name('roles.index');
        Volt::route('roles/create', 'admin.roles.create')->name('roles.create');
        Volt::route('roles/{role}', 'admin.roles.show')->name('roles.show');
        Volt::route('roles/{role}/edit', 'admin.roles.edit')->name('roles.edit');

        // Contribution Plans Management
        Volt::route('contribution-plans', 'admin.contribution-plans.index')->name('contribution-plans.index');
        Volt::route('contribution-plans/create', 'admin.contribution-plans.create')->name('contribution-plans.create');
        Volt::route('contribution-plans/{plan}', 'admin.contribution-plans.show')->name('contribution-plans.show');
        Volt::route('contribution-plans/{plan}/edit', 'admin.contribution-plans.edit')->name('contribution-plans.edit');

        // Healthcare Providers Management
        Volt::route('healthcare-providers', 'admin.healthcare-providers.index')->name('healthcare-providers.index');
        Volt::route('healthcare-providers/create', 'admin.healthcare-providers.create')->name('healthcare-providers.create');
        Volt::route('healthcare-providers/{provider}', 'admin.healthcare-providers.show')->name('healthcare-providers.show');
        Volt::route('healthcare-providers/{provider}/edit', 'admin.healthcare-providers.edit')->name('healthcare-providers.edit');

        // States and LGAs Management
        Volt::route('states', 'admin.states.index')->name('states.index');
        Volt::route('lgas', 'admin.lgas.index')->name('lgas.index');

        // Cashout Management
        Volt::route('cashout', 'admin.cashout.index')->name('cashout.index');
        Volt::route('cashout/{request}', 'admin.cashout.show')->name('cashout.show');

        // System Settings
        Volt::route('settings', 'admin.settings.index')->name('settings.index');
    });
});
