<?php

namespace Tests\Feature\Procurement;

use App\Actions\Procurement\ApprovePurchaseRequestAction;
use App\Domain\Procurement\Events\PurchaseRequestApproved;
use App\Enums\ApprovalStatus;
use App\Enums\PurchaseRequestSource;
use App\Enums\PurchaseRequestStatus;
use App\Models\Company;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ApprovePurchaseRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_approves_purchase_request()
    {
        Event::fake();

        $company = Company::factory()->create();
        $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'active',
        ]);

        Gate::before(fn () => true);

        $purchaseRequest = PurchaseRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PurchaseRequestStatus::WaitingApproval,
            'source' => PurchaseRequestSource::ManualStaff,
            'created_by' => User::factory()->create()->id,
        ]);

        $action = app(ApprovePurchaseRequestAction::class);
        $result = $action->execute($user, $purchaseRequest);

        $this->assertEquals(PurchaseRequestStatus::Approved, $result->status);
        $this->assertNotNull($result->approved_at);

        $this->assertDatabaseHas('approvals', [
            'approvable_type' => $purchaseRequest->getMorphClass(),
            'approvable_id' => $purchaseRequest->id,
            'warehouse_id' => $warehouse->id,
            'approver_id' => $user->id,
            'status' => ApprovalStatus::Approved->value,
        ]);

        Event::assertDispatched(PurchaseRequestApproved::class);
    }
}
