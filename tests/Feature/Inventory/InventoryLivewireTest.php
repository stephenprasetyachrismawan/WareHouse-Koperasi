<?php

declare(strict_types=1);

use App\Enums\MovementType;
use App\Enums\Permission;
use App\Livewire\Inventory\Items\Create as ItemsCreate;
use App\Livewire\Inventory\Items\Edit as ItemsEdit;
use App\Livewire\Inventory\Items\Index as ItemsIndex;
use App\Livewire\Inventory\Locations\Index as LocationsIndex;
use App\Livewire\Inventory\Stock\Ledger as StockLedger;
use App\Livewire\Inventory\Stock\Movement as StockMovement;
use App\Livewire\Inventory\Stock\Overview as StockOverview;
use App\Livewire\Inventory\Suppliers\Index as SuppliersIndex;
use App\Models\Company;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Support\Str;
use Livewire\Livewire;

function createInventoryUser(array $permissions = [Permission::ItemViewAny, Permission::ItemCreate, Permission::ItemUpdate, Permission::StockView, Permission::StockAdjust, Permission::StockScanIn, Permission::StockLedgerView, Permission::SupplierViewAny, Permission::SupplierManage, Permission::LocationViewAny, Permission::LocationManage]): array
{
    $company = Company::factory()->create(['status' => 'active']);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    $user = User::factory()->create(['status' => 'active']);

    $membership = WarehouseMembership::factory()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'user_id' => $user->id,
        'role' => 'staff_admin',
        'status' => 'active',
        'permissions' => array_map(fn ($p) => $p instanceof Permission ? $p->value : $p, $permissions),
    ]);

    return compact('company', 'warehouse', 'user', 'membership');
}

describe('Inventory Livewire Components', function () {
    test('Items Index displays items for active warehouse', function () {
        ['warehouse' => $warehouse, 'user' => $user] = createInventoryUser();

        $item1 = Item::factory()->create(['warehouse_id' => $warehouse->id, 'name' => 'Beras Organik']);
        $otherCompany = Company::factory()->create();
        $otherWh = Warehouse::factory()->create(['company_id' => $otherCompany->id]);
        $item2 = Item::factory()->create(['warehouse_id' => $otherWh->id, 'name' => 'Beras Non-Tenant']);

        $this->actingAs($user);

        Livewire::test(ItemsIndex::class)
            ->assertSee('Beras Organik')
            ->assertDontSee('Beras Non-Tenant');
    });

    test('Items Create can add a new item master record', function () {
        ['user' => $user, 'warehouse' => $warehouse] = createInventoryUser();

        $this->actingAs($user);

        Livewire::test(ItemsCreate::class)
            ->set('code', 'SKU-TEST-99')
            ->set('name', 'Minyak Goreng Sawit 2L')
            ->set('unit', 'PCH')
            ->set('minimum_stock', 15)
            ->set('barcode', '8991234567890')
            ->call('save')
            ->assertRedirect(route('inventory.items.index'));

        $this->assertDatabaseHas('items', [
            'warehouse_id' => $warehouse->id,
            'code' => 'SKU-TEST-99',
            'name' => 'Minyak Goreng Sawit 2L',
            'unit' => 'PCH',
            'minimum_stock' => 15,
        ]);

        $this->assertDatabaseHas('item_barcodes', [
            'warehouse_id' => $warehouse->id,
            'barcode' => '8991234567890',
            'is_primary' => true,
        ]);
    });

    test('Items Edit can update item details', function () {
        ['user' => $user, 'warehouse' => $warehouse] = createInventoryUser();
        $item = Item::factory()->create(['warehouse_id' => $warehouse->id, 'name' => 'Nama Lama']);

        $this->actingAs($user);

        Livewire::test(ItemsEdit::class, ['item' => $item])
            ->set('name', 'Nama Baru Terupdate')
            ->set('unit', 'BOX')
            ->set('minimum_stock', 50)
            ->call('save')
            ->assertRedirect(route('inventory.items.index'));

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'name' => 'Nama Baru Terupdate',
            'unit' => 'BOX',
            'minimum_stock' => 50,
        ]);
    });

    test('Stock Overview displays current physical balance and critical stock alerts', function () {
        ['user' => $user, 'warehouse' => $warehouse] = createInventoryUser();

        $itemCritical = Item::factory()->create([
            'warehouse_id' => $warehouse->id,
            'name' => 'Barang Kritis',
            'minimum_stock' => 20,
        ]);
        StockBalance::create(['warehouse_id' => $warehouse->id, 'item_id' => $itemCritical->id, 'quantity' => 5]);

        $this->actingAs($user);

        Livewire::test(StockOverview::class)
            ->assertSee('Barang Kritis')
            ->assertSee('KRITIS');
    });

    test('Stock Movement records stock adjustment and updates physical balance', function () {
        ['user' => $user, 'warehouse' => $warehouse] = createInventoryUser();
        $item = Item::factory()->create(['warehouse_id' => $warehouse->id]);

        $this->actingAs($user);

        Livewire::test(StockMovement::class)
            ->set('item_id', $item->id)
            ->set('movement_type', 'OPENING_BALANCE')
            ->set('quantity', 150)
            ->set('reason', 'Pencatatan stok awal gudang')
            ->call('save')
            ->assertRedirect(route('inventory.stock.overview'));

        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'quantity' => 150,
        ]);

        $this->assertDatabaseHas('stock_transactions', [
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'movement_type' => MovementType::OpeningBalance->value,
            'signed_quantity' => 150,
            'reason' => 'Pencatatan stok awal gudang',
        ]);
    });

    test('Stock Ledger displays append-only immutable history', function () {
        ['user' => $user, 'warehouse' => $warehouse] = createInventoryUser();
        $item = Item::factory()->create(['warehouse_id' => $warehouse->id, 'name' => 'Gula Pasir 1KG']);

        StockTransaction::create([
            'uuid' => (string) Str::uuid(),
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'movement_type' => MovementType::OpeningBalance->value,
            'signed_quantity' => 500,
            'balance_before' => 0,
            'balance_after' => 500,
            'reason' => 'Stok awal 500 KG',
            'performed_by' => $user->id,
            'idempotency_key' => 'key-gula-1',
            'occurred_at' => now(),
            'created_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(StockLedger::class)
            ->assertSee('Gula Pasir 1KG')
            ->assertSee('Saldo Awal')
            ->assertSee('+500');
    });

    test('Suppliers Index can create a supplier record', function () {
        ['user' => $user, 'warehouse' => $warehouse] = createInventoryUser();

        $this->actingAs($user);

        Livewire::test(SuppliersIndex::class)
            ->set('name', 'PT Koperasi Jaya Supplier')
            ->set('contact_name', 'Budi Sales')
            ->set('email', 'sales@koperasijaya.id')
            ->set('phone', '081234567890')
            ->call('saveSupplier');

        $this->assertDatabaseHas('suppliers', [
            'warehouse_id' => $warehouse->id,
            'name' => 'PT Koperasi Jaya Supplier',
            'contact_name' => 'Budi Sales',
        ]);
    });

    test('Locations Index can create a warehouse location record', function () {
        ['user' => $user, 'warehouse' => $warehouse] = createInventoryUser();

        $this->actingAs($user);

        Livewire::test(LocationsIndex::class)
            ->set('code', 'RAK-B01')
            ->set('name', 'Rak Sembako Utama')
            ->set('description', 'Lantai 1 Zona B')
            ->call('saveLocation');

        $this->assertDatabaseHas('warehouse_locations', [
            'warehouse_id' => $warehouse->id,
            'code' => 'RAK-B01',
            'name' => 'Rak Sembako Utama',
        ]);
    });

    test('User without permission cannot access inventory pages', function () {
        ['user' => $user] = createInventoryUser([]); // empty permissions

        $this->actingAs($user);

        $this->get(route('inventory.items.index'))->assertStatus(403);
        $this->get(route('inventory.stock.overview'))->assertStatus(403);
        $this->get(route('inventory.suppliers.index'))->assertStatus(403);
    });
});
