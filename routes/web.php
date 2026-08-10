<?php

use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Middleware\EnsureTenantContext;
use App\Livewire\Company\Users\Create as CompanyUsersCreate;
use App\Livewire\Company\Users\Edit as CompanyUsersEdit;
use App\Livewire\Company\Users\Index as CompanyUsersIndex;
use App\Livewire\Inventory\Items\Create as InventoryItemsCreate;
use App\Livewire\Inventory\Items\Edit as InventoryItemsEdit;
use App\Livewire\Inventory\Items\Index as InventoryItemsIndex;
use App\Livewire\Inventory\Locations\Index as InventoryLocationsIndex;
use App\Livewire\Inventory\Stock\Ledger as InventoryStockLedger;
use App\Livewire\Inventory\Stock\Movement as InventoryStockMovement;
use App\Livewire\Inventory\Stock\Overview as InventoryStockOverview;
use App\Livewire\Inventory\Suppliers\Index as InventorySuppliersIndex;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::name('auth.google.')->middleware('guest')->group(function () {
    Route::get('auth/google', [SocialiteController::class, 'redirectToGoogle'])->name('redirect');
    Route::get('auth/google/callback', [SocialiteController::class, 'handleGoogleCallback'])->name('callback');
    Route::get('auth/google/complete', [SocialiteController::class, 'showCompleteForm'])->name('complete');
    Route::post('auth/google/complete', [SocialiteController::class, 'completeCompany'])->name('complete.store');
});

Route::middleware(['auth', 'verified', EnsureTenantContext::class])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Tenant User Management Routes
    Route::prefix('company')->name('company.')->group(function () {
        Route::get('users', CompanyUsersIndex::class)->name('users.index');
        Route::get('users/create', CompanyUsersCreate::class)->name('users.create');
        Route::get('users/{user}/edit', CompanyUsersEdit::class)->name('users.edit');
    });

    // Inventory Foundation Routes
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('items', InventoryItemsIndex::class)->name('items.index');
        Route::get('items/create', InventoryItemsCreate::class)->name('items.create');
        Route::get('items/{item}/edit', InventoryItemsEdit::class)->name('items.edit');

        Route::get('stock', InventoryStockOverview::class)->name('stock.overview');
        Route::get('stock/movement', InventoryStockMovement::class)->name('stock.movement');
        Route::get('stock/ledger', InventoryStockLedger::class)->name('stock.ledger');

        Route::get('suppliers', InventorySuppliersIndex::class)->name('suppliers.index');
        Route::get('locations', InventoryLocationsIndex::class)->name('locations.index');
    });
});

require __DIR__.'/settings.php';
