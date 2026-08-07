<?php

use App\Http\Middleware\EnsureTenantContext;
use App\Livewire\Company\Users\Create as CompanyUsersCreate;
use App\Livewire\Company\Users\Edit as CompanyUsersEdit;
use App\Livewire\Company\Users\Index as CompanyUsersIndex;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified', EnsureTenantContext::class])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Tenant User Management Routes
    Route::prefix('company')->name('company.')->group(function () {
        Route::get('users', CompanyUsersIndex::class)->name('users.index');
        Route::get('users/create', CompanyUsersCreate::class)->name('users.create');
        Route::get('users/{user}/edit', CompanyUsersEdit::class)->name('users.edit');
    });
});

require __DIR__.'/settings.php';
