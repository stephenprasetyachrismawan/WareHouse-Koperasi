<?php

namespace Tests\Feature\Procurement;

use App\Actions\Procurement\ApprovePurchaseCancellationAction;
use App\Domain\Procurement\Events\CancellationApproved;
use App\Domain\Procurement\Events\PurchaseRequestCancelled;
use App\Enums\CancellationRequestStatus;
use App\Enums\Permission;
use App\Enums\PurchaseRequestStatus;
use App\Models\CancellationRequest;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ApproveCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_approves_cancellation_and_cancels_pr(): void
    {
        Event::fake();

        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        setPermissionsTeamId($warehouse->company_id);
        $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => Permission::PurchaseRequestCancel->value]);
        Gate::before(fn () => true);
        WarehouseMembership::factory()->create(['user_id' => $user->id, 'warehouse_id' => $warehouse->id, 'company_id' => $warehouse->company_id, 'status' => 'active']);

        $pr = PurchaseRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PurchaseRequestStatus::Approved,
        ]);

        $cr = CancellationRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'purchase_request_id' => $pr->id,
            'status' => CancellationRequestStatus::Pending,
            'reason' => 'Requested reason',
        ]);

        $action = app(ApprovePurchaseCancellationAction::class);
        $updatedPr = $action->execute($user, $cr, 'Approved reason');

        $this->assertDatabaseHas('cancellation_requests', [
            'id' => $cr->id,
            'status' => CancellationRequestStatus::Approved->value,
            'decided_by' => $user->id,
            'decision_reason' => 'Approved reason',
        ]);

        $this->assertDatabaseHas('purchase_requests', [
            'id' => $pr->id,
            'status' => PurchaseRequestStatus::Cancelled->value,
            'cancellation_reason' => 'Requested reason',
        ]);

        $this->assertNotNull($updatedPr->cancelled_at);

        Event::assertDispatched(CancellationApproved::class, function ($e) use ($cr) {
            return $e->cancellationRequest->id === $cr->id;
        });

        Event::assertDispatched(PurchaseRequestCancelled::class, function ($e) use ($pr) {
            return $e->purchaseRequest->id === $pr->id && $e->reason === 'Requested reason';
        });
    }
}
