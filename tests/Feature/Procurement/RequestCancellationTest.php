<?php

namespace Tests\Feature\Procurement;

use App\Actions\Procurement\RequestPurchaseCancellationAction;
use App\Domain\Procurement\Events\CancellationRequested;
use App\Enums\CancellationRequestStatus;
use App\Enums\Permission;
use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RequestCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_cancellation_request_and_dispatches_event(): void
    {
        Event::fake();

        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        setPermissionsTeamId($warehouse->company_id);
        $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => Permission::PurchaseRequestRequestCancellation->value]);
        Gate::before(fn () => true);
        WarehouseMembership::factory()->create(['user_id' => $user->id, 'warehouse_id' => $warehouse->id, 'company_id' => $warehouse->company_id, 'status' => 'active']);

        $pr = PurchaseRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PurchaseRequestStatus::Approved,
        ]);

        $action = app(RequestPurchaseCancellationAction::class);
        $cr = $action->execute($user, $pr, 'Found cheaper vendor');

        $this->assertDatabaseHas('cancellation_requests', [
            'id' => $cr->id,
            'warehouse_id' => $warehouse->id,
            'purchase_request_id' => $pr->id,
            'requested_by' => $user->id,
            'reason' => 'Found cheaper vendor',
            'status' => CancellationRequestStatus::Pending->value,
        ]);

        Event::assertDispatched(CancellationRequested::class, function ($e) use ($cr) {
            return $e->cancellationRequest->id === $cr->id;
        });
    }

    public function test_it_fails_if_unauthorized(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        setPermissionsTeamId($warehouse->company_id);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => Permission::PurchaseRequestRequestCancellation->value, 'guard_name' => 'web']);
        WarehouseMembership::factory()->create(['user_id' => $user->id, 'warehouse_id' => $warehouse->id, 'company_id' => $warehouse->company_id, 'status' => 'active']);

        $pr = PurchaseRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PurchaseRequestStatus::Approved,
        ]);

        $action = app(RequestPurchaseCancellationAction::class);
        $this->expectException(AuthorizationException::class);
        $action->execute($user, $pr, 'Reason');
    }

    public function test_it_fails_if_reason_is_empty(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        setPermissionsTeamId($warehouse->company_id);
        $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => Permission::PurchaseRequestRequestCancellation->value]);
        Gate::before(fn () => true);
        WarehouseMembership::factory()->create(['user_id' => $user->id, 'warehouse_id' => $warehouse->id, 'company_id' => $warehouse->company_id, 'status' => 'active']);

        $pr = PurchaseRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PurchaseRequestStatus::Approved,
        ]);

        $action = app(RequestPurchaseCancellationAction::class);
        $this->expectException(ValidationException::class);
        $action->execute($user, $pr, '   ');
    }

    public function test_it_fails_if_pr_is_terminal(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        setPermissionsTeamId($warehouse->company_id);
        $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => Permission::PurchaseRequestRequestCancellation->value]);
        Gate::before(fn () => true);
        WarehouseMembership::factory()->create(['user_id' => $user->id, 'warehouse_id' => $warehouse->id, 'company_id' => $warehouse->company_id, 'status' => 'active']);

        $pr = PurchaseRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PurchaseRequestStatus::Completed,
        ]);

        $action = app(RequestPurchaseCancellationAction::class);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Purchase request cannot be cancelled at this stage.');
        $action->execute($user, $pr, 'Reason');
    }
}
