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
    });

    // Loan Management Routes
    Route::prefix('loans')->name('loans.')->group(function () {
        Volt::route('/', 'loans.index')->name('index');
        Volt::route('/create', 'loans.create')->name('create');
        Volt::route('/{loan}', 'loans.show')->name('show');
        Volt::route('/{loan}/edit', 'loans.edit')->name('edit');
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

        // System Settings
        Volt::route('settings', 'admin.settings.index')->name('settings.index');
    });
});
