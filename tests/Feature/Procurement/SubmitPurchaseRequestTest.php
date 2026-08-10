<?php

namespace Tests\Feature\Procurement;

use App\Actions\Procurement\SubmitPurchaseForApprovalAction;
use App\Domain\Procurement\Events\PurchaseRequestSubmitted;
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

class SubmitPurchaseRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_submits_purchase_request_for_approval()
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
            'status' => PurchaseRequestStatus::Draft,
            'source' => PurchaseRequestSource::ManualStaff,
            'created_by' => $user->id,
        ]);

        $action = app(SubmitPurchaseForApprovalAction::class);
        $result = $action->execute($user, $purchaseRequest);

        $this->assertEquals(PurchaseRequestStatus::WaitingApproval, $result->status);
        $this->assertNotNull($result->submitted_at);

        Event::assertDispatched(PurchaseRequestSubmitted::class, function ($e) use ($purchaseRequest) {
            return $e->purchaseRequest->id === $purchaseRequest->id;
        });
    }
}
