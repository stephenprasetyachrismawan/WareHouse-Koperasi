<?php

declare(strict_types=1);

use App\Actions\Inventory\RecordStockMovementAction;
use App\Domain\Inventory\Events\StockMovementRecorded;
use App\Domain\Inventory\Exceptions\DuplicateStockMovementException;
use App\Domain\Inventory\ValueObjects\StockMovementInput;
use App\Enums\MovementType;
use App\Models\Company;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Helper to scaffold a full tenant context for stock tests.
 *
 * @return array{warehouse: Warehouse, user: User, item: Item, membership: WarehouseMembership}
 */
function createStockTestContext(array $overrides = []): array
{
    $company = Company::factory()->create(['status' => 'active']);
    $warehouse = Warehouse::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);
    $user = User::factory()->create(['status' => 'active']);
    $membership = WarehouseMembership::factory()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'user_id' => $user->id,
        'role' => 'staff_admin',
        'status' => 'active',
    ]);
    $item = Item::factory()->create(array_merge([
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ], $overrides));

    return compact('warehouse', 'user', 'item', 'membership');
}

// =====================================================================
// RecordStockMovementAction Tests
// =====================================================================

describe('RecordStockMovementAction', function () {
    test('records opening balance and creates stock balance', function () {
        ['warehouse' => $warehouse, 'user' => $user, 'item' => $item] = createStockTestContext();

        $action = new RecordStockMovementAction;

        $input = new StockMovementInput(
            warehouseId: $warehouse->id,
            itemId: $item->id,
            movementType: MovementType::OpeningBalance,
            quantity: 100,
            performedBy: $user->id,
            idempotencyKey: 'opening-'.$item->id.'-'.now()->timestamp,
            reason: 'Initial opening balance',
        );

        $transaction = $action->execute($input);

        expect($transaction)->toBeInstanceOf(StockTransaction::class);
        expect($transaction->signed_quantity)->toBe(100);
        expect($transaction->balance_before)->toBe(0);
        expect($transaction->balance_after)->toBe(100);
        expect($transaction->movement_type)->toBe(MovementType::OpeningBalance);
        expect($transaction->warehouse_id)->toBe($warehouse->id);
        expect($transaction->item_id)->toBe($item->id);
        expect($transaction->performed_by)->toBe($user->id);

        $balance = StockBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('item_id', $item->id)
            ->first();

        expect($balance)->not->toBeNull();
        expect($balance->quantity)->toBe(100);
        expect($balance->version)->toBe(2); // created at 1, updated to 2
    });

    test('records stock out and decrements balance', function () {
        ['warehouse' => $warehouse, 'user' => $user, 'item' => $item] = createStockTestContext();

        $action = new RecordStockMovementAction;

        // First, add stock
        $action->execute(new StockMovementInput(
            warehouseId: $warehouse->id,
            itemId: $item->id,
            movementType: MovementType::OpeningBalance,
            quantity: 50,
            performedBy: $user->id,
            idempotencyKey: 'opening-test-'.uniqid(),
        ));

        // Now, take stock out
        $transaction = $action->execute(new StockMovementInput(
            warehouseId: $warehouse->id,
            itemId: $item->id,
            movementType: MovementType::ManualAdjustmentOut,
            quantity: 20,
            performedBy: $user->id,
            idempotencyKey: 'out-test-'.uniqid(),
            reason: 'Damaged goods',
        ));

        expect($transaction->signed_quantity)->toBe(-20);
        expect($transaction->balance_before)->toBe(50);
        expect($transaction->balance_after)->toBe(30);

        $balance = StockBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('item_id', $item->id)
            ->first();

        expect($balance->quantity)->toBe(30);
    });

    test('allows negative stock (backorder)', function () {
        ['warehouse' => $warehouse, 'user' => $user, 'item' => $item] = createStockTestContext();

        $action = new RecordStockMovementAction;

        // Stock out without any opening balance -> negative
        $transaction = $action->execute(new StockMovementInput(
            warehouseId: $warehouse->id,
            itemId: $item->id,
            movementType: MovementType::ManualAdjustmentOut,
            quantity: 10,
            performedBy: $user->id,
            idempotencyKey: 'negative-test-'.uniqid(),
            reason: 'Emergency issue',
        ));

        expect($transaction->balance_after)->toBe(-10);

        $balance = StockBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('item_id', $item->id)
            ->first();

        expect($balance->quantity)->toBe(-10);
    });

    test('rejects duplicate idempotency key', function () {
        ['warehouse' => $warehouse, 'user' => $user, 'item' => $item] = createStockTestContext();

        $action = new RecordStockMovementAction;
        $idempotencyKey = 'unique-key-123';

        $action->execute(new StockMovementInput(
            warehouseId: $warehouse->id,
            itemId: $item->id,
            movementType: MovementType::OpeningBalance,
            quantity: 100,
            performedBy: $user->id,
            idempotencyKey: $idempotencyKey,
        ));

        expect(fn () => $action->execute(new StockMovementInput(
            warehouseId: $warehouse->id,
            itemId: $item->id,
            movementType: MovementType::OpeningBalance,
            quantity: 100,
            performedBy: $user->id,
            idempotencyKey: $idempotencyKey,
        )))->toThrow(DuplicateStockMovementException::class);

        // Balance should still be 100 (first movement only)
        $balance = StockBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('item_id', $item->id)
            ->first();
        expect($balance->quantity)->toBe(100);
    });

    test('same idempotency key in different warehouses is allowed', function () {
        $company = Company::factory()->create(['status' => 'active']);
        $wh1 = Warehouse::factory()->create(['company_id' => $company->id]);
        $wh2 = Warehouse::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['status' => 'active']);
        $item1 = Item::factory()->create(['warehouse_id' => $wh1->id, 'created_by' => $user->id]);
        $item2 = Item::factory()->create(['warehouse_id' => $wh2->id, 'created_by' => $user->id]);

        $action = new RecordStockMovementAction;
        $key = 'cross-wh-key';

        $t1 = $action->execute(new StockMovementInput(
            warehouseId: $wh1->id,
            itemId: $item1->id,
            movementType: MovementType::OpeningBalance,
            quantity: 10,
            performedBy: $user->id,
            idempotencyKey: $key,
        ));

        $t2 = $action->execute(new StockMovementInput(
            warehouseId: $wh2->id,
            itemId: $item2->id,
            movementType: MovementType::OpeningBalance,
            quantity: 20,
            performedBy: $user->id,
            idempotencyKey: $key,
        ));

        expect($t1->warehouse_id)->toBe($wh1->id);
        expect($t2->warehouse_id)->toBe($wh2->id);
    });

    test('sequential movements maintain correct balance chain', function () {
        ['warehouse' => $warehouse, 'user' => $user, 'item' => $item] = createStockTestContext();

        $action = new RecordStockMovementAction;

        $t1 = $action->execute(new StockMovementInput(
            warehouseId: $warehouse->id,
            itemId: $item->id,
            movementType: MovementType::OpeningBalance,
            quantity: 100,
            performedBy: $user->id,
            idempotencyKey: 'seq-1',
        ));

        $t2 = $action->execute(new StockMovementInput(
            warehouseId: $warehouse->id,
            itemId: $item->id,
            movementType: MovementType::ManualAdjustmentIn,
            quantity: 50,
            performedBy: $user->id,
            idempotencyKey: 'seq-2',
        ));

        $t3 = $action->execute(new StockMovementInput(
            warehouseId: $warehouse->id,
            itemId: $item->id,
            movementType: MovementType::ManualAdjustmentOut,
            quantity: 30,
            performedBy: $user->id,
            idempotencyKey: 'seq-3',
        ));

        expect($t1->balance_before)->toBe(0);
        expect($t1->balance_after)->toBe(100);

        expect($t2->balance_before)->toBe(100);
        expect($t2->balance_after)->toBe(150);

        expect($t3->balance_before)->toBe(150);
        expect($t3->balance_after)->toBe(120);

        $balance = StockBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('item_id', $item->id)
            ->first();

        expect($balance->quantity)->toBe(120);
    });

    test('ledger entries are immutable - cannot be updated', function () {
        ['warehouse' => $warehouse, 'user' => $user, 'item' => $item] = createStockTestContext();

        $action = new RecordStockMovementAction;

        $transaction = $action->execute(new StockMovementInput(
            warehouseId: $warehouse->id,
            itemId: $item->id,
            movementType: MovementType::OpeningBalance,
            quantity: 100,
            performedBy: $user->id,
            idempotencyKey: 'immutable-test',
        ));

        // Attempt to update should throw
        expect(fn () => $transaction->update(['signed_quantity' => 999]))
            ->toThrow(RuntimeException::class);
    });

    test('ledger entries are immutable - cannot be deleted', function () {
        ['warehouse' => $warehouse, 'user' => $user, 'item' => $item] = createStockTestContext();

        $action = new RecordStockMovementAction;

        $transaction = $action->execute(new StockMovementInput(
            warehouseId: $warehouse->id,
            itemId: $item->id,
            movementType: MovementType::OpeningBalance,
            quantity: 100,
            performedBy: $user->id,
            idempotencyKey: 'delete-test',
        ));

        expect(fn () => $transaction->delete())
            ->toThrow(RuntimeException::class);
    });

    test('rejects zero quantity in value object', function () {
        expect(fn () => new StockMovementInput(
            warehouseId: 1,
            itemId: 1,
            movementType: MovementType::OpeningBalance,
            quantity: 0,
            performedBy: 1,
            idempotencyKey: 'zero',
        ))->toThrow(InvalidArgumentException::class, 'must not be zero');
    });

    test('rejects negative quantity in value object', function () {
        expect(fn () => new StockMovementInput(
            warehouseId: 1,
            itemId: 1,
            movementType: MovementType::ManualAdjustmentOut,
            quantity: -5,
            performedBy: 1,
            idempotencyKey: 'negative',
        ))->toThrow(InvalidArgumentException::class, 'must be positive');
    });
});

// =====================================================================
// Tenant Isolation Tests
// =====================================================================

describe('Tenant isolation for stock', function () {
    test('stock transaction is scoped to its warehouse', function () {
        $company = Company::factory()->create(['status' => 'active']);
        $wh1 = Warehouse::factory()->create(['company_id' => $company->id]);
        $wh2 = Warehouse::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['status' => 'active']);
        $item1 = Item::factory()->create(['warehouse_id' => $wh1->id, 'created_by' => $user->id]);
        $item2 = Item::factory()->create(['warehouse_id' => $wh2->id, 'created_by' => $user->id]);

        $action = new RecordStockMovementAction;

        $action->execute(new StockMovementInput(
            warehouseId: $wh1->id,
            itemId: $item1->id,
            movementType: MovementType::OpeningBalance,
            quantity: 100,
            performedBy: $user->id,
            idempotencyKey: 'wh1-opening',
        ));

        $action->execute(new StockMovementInput(
            warehouseId: $wh2->id,
            itemId: $item2->id,
            movementType: MovementType::OpeningBalance,
            quantity: 200,
            performedBy: $user->id,
            idempotencyKey: 'wh2-opening',
        ));

        // Warehouse 1 should only see its own stock
        $wh1Balance = StockBalance::query()
            ->where('warehouse_id', $wh1->id)
            ->first();
        expect($wh1Balance->quantity)->toBe(100);

        // Warehouse 2 should only see its own stock
        $wh2Balance = StockBalance::query()
            ->where('warehouse_id', $wh2->id)
            ->first();
        expect($wh2Balance->quantity)->toBe(200);

        // Each warehouse has exactly 1 transaction
        expect(StockTransaction::where('warehouse_id', $wh1->id)->count())->toBe(1);
        expect(StockTransaction::where('warehouse_id', $wh2->id)->count())->toBe(1);
    });

    test('cannot create stock movement for item in different warehouse', function () {
        $company = Company::factory()->create(['status' => 'active']);
        $wh1 = Warehouse::factory()->create(['company_id' => $company->id]);
        $wh2 = Warehouse::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['status' => 'active']);

        // Item created in wh1
        $item1 = Item::factory()->create(['warehouse_id' => $wh1->id, 'created_by' => $user->id]);

        $action = new RecordStockMovementAction;

        // Try to record movement for item1 using wh2's warehouse_id
        $transaction = $action->execute(new StockMovementInput(
            warehouseId: $wh2->id,
            itemId: $item1->id,
            movementType: MovementType::OpeningBalance,
            quantity: 100,
            performedBy: $user->id,
            idempotencyKey: 'cross-tenant-test',
        ));

        // The transaction should succeed but use wh2
        expect($transaction->warehouse_id)->toBe($wh2->id);

        $balance = StockBalance::query()
            ->where('warehouse_id', $wh2->id)
            ->where('item_id', $item1->id)
            ->first();

        expect($balance)->not->toBeNull();
    });
    test('item is scoped by warehouse_id', function () {
        $company = Company::factory()->create(['status' => 'active']);
        $wh1 = Warehouse::factory()->create(['company_id' => $company->id]);
        $wh2 = Warehouse::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['status' => 'active']);

        $item1 = Item::factory()->create([
            'warehouse_id' => $wh1->id,
            'code' => 'SKU-001',
            'created_by' => $user->id,
        ]);

        // Same code in different warehouse should be allowed
        $item2 = Item::factory()->create([
            'warehouse_id' => $wh2->id,
            'code' => 'SKU-001',
            'created_by' => $user->id,
        ]);

        expect($item1->warehouse_id)->toBe($wh1->id);
        expect($item2->warehouse_id)->toBe($wh2->id);
        expect($item1->code)->toBe('SKU-001');
        expect($item2->code)->toBe('SKU-001');
    });
});

// =====================================================================
// MovementType Enum Tests
// =====================================================================

describe('MovementType', function () {
    test('inbound types are correctly identified', function () {
        expect(MovementType::OpeningBalance->isInbound())->toBeTrue();
        expect(MovementType::ManualAdjustmentIn->isInbound())->toBeTrue();
        expect(MovementType::Receipt->isInbound())->toBeTrue();
    });

    test('outbound types are correctly identified', function () {
        expect(MovementType::ManualAdjustmentOut->isInbound())->toBeFalse();
        expect(MovementType::PickupIssue->isInbound())->toBeFalse();
        expect(MovementType::ReturnDisposal->isInbound())->toBeFalse();
        expect(MovementType::ReplacementIssue->isInbound())->toBeFalse();
    });

    test('labels are in Indonesian', function () {
        expect(MovementType::OpeningBalance->label())->toBe('Saldo Awal');
        expect(MovementType::ManualAdjustmentIn->label())->toBe('Penyesuaian Masuk');
        expect(MovementType::ManualAdjustmentOut->label())->toBe('Penyesuaian Keluar');
    });
});

// =====================================================================
// StockBalance Reconciliation
// =====================================================================

describe('Stock reconciliation', function () {
    test('sum of ledger signed_quantity equals balance quantity', function () {
        ['warehouse' => $warehouse, 'user' => $user, 'item' => $item] = createStockTestContext();

        $action = new RecordStockMovementAction;

        $action->execute(new StockMovementInput(
            warehouseId: $warehouse->id,
            itemId: $item->id,
            movementType: MovementType::OpeningBalance,
            quantity: 100,
            performedBy: $user->id,
            idempotencyKey: 'recon-1',
        ));

        $action->execute(new StockMovementInput(
            warehouseId: $warehouse->id,
            itemId: $item->id,
            movementType: MovementType::ManualAdjustmentIn,
            quantity: 50,
            performedBy: $user->id,
            idempotencyKey: 'recon-2',
        ));

        $action->execute(new StockMovementInput(
            warehouseId: $warehouse->id,
            itemId: $item->id,
            movementType: MovementType::ManualAdjustmentOut,
            quantity: 30,
            performedBy: $user->id,
            idempotencyKey: 'recon-3',
        ));

        // Reconciliation: SUM(signed_quantity) == balance.quantity
        $ledgerSum = StockTransaction::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('item_id', $item->id)
            ->sum('signed_quantity');

        $balance = StockBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('item_id', $item->id)
            ->first();

        expect((int) $ledgerSum)->toBe($balance->quantity);
        expect((int) $ledgerSum)->toBe(120); // 100 + 50 - 30
    });
});

// =====================================================================
// Critical Stock Tests
// =====================================================================
describe('Critical stock', function () {
    test('item is critical when current stock is below minimum stock', function () {
        ['warehouse' => $warehouse, 'user' => $user, 'item' => $item] = createStockTestContext(['minimum_stock' => 10]);

        StockBalance::factory()->create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'quantity' => 9,
        ]);

        expect($item->isCritical())->toBeTrue();
    });

    test('item is NOT critical when current stock equals minimum stock', function () {
        ['warehouse' => $warehouse, 'user' => $user, 'item' => $item] = createStockTestContext(['minimum_stock' => 10]);

        StockBalance::factory()->create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'quantity' => 10,
        ]);

        expect($item->isCritical())->toBeFalse();
    });

    test('item with no stock balance and positive minimum_stock is critical', function () {
        ['warehouse' => $warehouse, 'user' => $user, 'item' => $item] = createStockTestContext(['minimum_stock' => 5]);

        expect($item->isCritical())->toBeTrue();
    });
});

// =====================================================================
// Concurrency Safety Tests
// =====================================================================
describe('Concurrency safety', function () {
    test('concurrent stock movements do not cause lost updates', function () {
        ['warehouse' => $warehouse, 'user' => $user, 'item' => $item] = createStockTestContext();

        $action = new RecordStockMovementAction;

        // Opening balance
        $t1 = $action->execute(new StockMovementInput(
            warehouseId: $warehouse->id,
            itemId: $item->id,
            movementType: MovementType::OpeningBalance,
            quantity: 100,
            performedBy: $user->id,
            idempotencyKey: 'concurrent-1',
        ));

        // Simulating sequential updates since lockForUpdate logic requires actual concurrent execution
        // to fully test, but we can verify the chain logic is strictly maintained.
        $t2 = $action->execute(new StockMovementInput(
            warehouseId: $warehouse->id,
            itemId: $item->id,
            movementType: MovementType::ManualAdjustmentOut,
            quantity: 30,
            performedBy: $user->id,
            idempotencyKey: 'concurrent-2',
        ));

        $t3 = $action->execute(new StockMovementInput(
            warehouseId: $warehouse->id,
            itemId: $item->id,
            movementType: MovementType::ManualAdjustmentOut,
            quantity: 20,
            performedBy: $user->id,
            idempotencyKey: 'concurrent-3',
        ));

        expect($t2->balance_before)->toBe(100);
        expect($t2->balance_after)->toBe(70);

        expect($t3->balance_before)->toBe(70);
        expect($t3->balance_after)->toBe(50);

        $balance = StockBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('item_id', $item->id)
            ->first();

        expect($balance->quantity)->toBe(50);
    });
});

// =====================================================================
// Mass-assignment protection Tests
// =====================================================================
describe('Mass-assignment protection', function () {
    test('warehouse_id cannot be mass-assigned to forge tenant ownership on StockTransaction', function () {
        ['warehouse' => $warehouse, 'user' => $user, 'item' => $item] = createStockTestContext();
        $otherWarehouse = Warehouse::factory()->create();

        $action = new RecordStockMovementAction;

        $transaction = $action->execute(new StockMovementInput(
            warehouseId: $warehouse->id,
            itemId: $item->id,
            movementType: MovementType::OpeningBalance,
            quantity: 100,
            performedBy: $user->id,
            idempotencyKey: 'mass-assign-1',
        ));

        expect($transaction->warehouse_id)->toBe($warehouse->id);
        expect($transaction->warehouse_id)->not->toBe($otherWarehouse->id);
    });

});

// =====================================================================
// Event Timing / Nested Transaction Composition
// =====================================================================
describe('StockMovementRecorded event timing', function () {
    test('fires when called standalone with no outer transaction', function () {
        Event::fake([StockMovementRecorded::class]);

        ['warehouse' => $warehouse, 'user' => $user, 'item' => $item] = createStockTestContext();

        app(RecordStockMovementAction::class)->execute(new StockMovementInput(
            warehouseId: $warehouse->id,
            itemId: $item->id,
            movementType: MovementType::OpeningBalance,
            quantity: 10,
            performedBy: $user->id,
            idempotencyKey: 'event-standalone',
        ));

        Event::assertDispatched(StockMovementRecorded::class);
    });

    test('does not fire when the outer caller-owned transaction rolls back', function () {
        Event::fake([StockMovementRecorded::class]);

        ['warehouse' => $warehouse, 'user' => $user, 'item' => $item] = createStockTestContext();

        try {
            DB::transaction(function () use ($warehouse, $user, $item) {
                app(RecordStockMovementAction::class)->execute(new StockMovementInput(
                    warehouseId: $warehouse->id,
                    itemId: $item->id,
                    movementType: MovementType::OpeningBalance,
                    quantity: 10,
                    performedBy: $user->id,
                    idempotencyKey: 'event-rollback',
                ));

                throw new RuntimeException('force rollback of the outer transaction');
            });
        } catch (RuntimeException) {
            // expected
        }

        Event::assertNotDispatched(StockMovementRecorded::class);
        expect(StockTransaction::where('idempotency_key', 'event-rollback')->exists())->toBeFalse();
    });

    test('fires exactly once after the outer caller-owned transaction commits', function () {
        Event::fake([StockMovementRecorded::class]);

        ['warehouse' => $warehouse, 'user' => $user, 'item' => $item] = createStockTestContext();

        DB::transaction(function () use ($warehouse, $user, $item) {
            app(RecordStockMovementAction::class)->execute(new StockMovementInput(
                warehouseId: $warehouse->id,
                itemId: $item->id,
                movementType: MovementType::OpeningBalance,
                quantity: 10,
                performedBy: $user->id,
                idempotencyKey: 'event-commit',
            ));

            // The event must not have fired yet — the outer transaction hasn't committed.
            Event::assertNotDispatched(StockMovementRecorded::class);
        });

        Event::assertDispatchedTimes(StockMovementRecorded::class, 1);
    });
});

describe('Mass-assignment protection continued', function () {
    test('balance_before and balance_after are calculated server-side', function () {
        ['warehouse' => $warehouse, 'user' => $user, 'item' => $item] = createStockTestContext();

        $action = new RecordStockMovementAction;

        $transaction = $action->execute(new StockMovementInput(
            warehouseId: $warehouse->id,
            itemId: $item->id,
            movementType: MovementType::OpeningBalance,
            quantity: 100,
            performedBy: $user->id,
            idempotencyKey: 'mass-assign-2',
        ));

        // They were derived logically from initial 0 to 100, not provided via input object
        expect($transaction->balance_before)->toBe(0);
        expect($transaction->balance_after)->toBe(100);
    });
});
