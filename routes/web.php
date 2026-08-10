<?php

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
use App\Livewire\Pickup\ApprovalInbox;
use App\Livewire\Pickup\Create;
use App\Livewire\Pickup\Fulfilment;
use App\Livewire\Pickup\MyRequests;
use App\Livewire\Pickup\Show;
use App\Livewire\Procurement\GroupingWorkspace;
use App\Livewire\Procurement\Index;
use App\Livewire\Procurement\PurchaseOrderIndex;
use App\Livewire\Procurement\PurchaseOrderShow;
use App\Livewire\Procurement\PurchasingQueue;
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

    // Pickup Request Routes
    Route::prefix('pickup')->name('pickup.')->group(function () {
        Route::get('create', Create::class)->name('create');
        Route::get('my-requests', MyRequests::class)->name('my-requests');
        Route::get('approval-inbox', ApprovalInbox::class)->name('approval-inbox');
        Route::get('fulfilment', Fulfilment::class)->name('fulfilment');
        Route::get('{pickupRequest:uuid}', Show::class)->name('show');
    });

    // Procurement Routes
    Route::prefix('procurement')->name('procurement.')->group(function () {
        Route::get('requests', Index::class)->name('index');
        Route::get('create', App\Livewire\Procurement\Create::class)->name('create');
        Route::get('approval-inbox', App\Livewire\Procurement\ApprovalInbox::class)->name('approval-inbox');
        Route::get('approved-queue', PurchasingQueue::class)->name('approved-queue');
        Route::get('requests/{purchaseRequest:uuid}', App\Livewire\Procurement\Show::class)->name('show');
        Route::get('grouping', GroupingWorkspace::class)->name('grouping');
        Route::get('purchase-orders', PurchaseOrderIndex::class)->name('purchase-orders.index');
        Route::get('purchase-orders/{purchaseOrder:uuid}', PurchaseOrderShow::class)->name('purchase-orders.show');
    });
});

require __DIR__.'/settings.php';
