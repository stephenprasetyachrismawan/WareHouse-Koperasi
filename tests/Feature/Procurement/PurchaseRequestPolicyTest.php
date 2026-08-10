<?php

namespace Tests\Feature\Procurement;

use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use App\Policies\PurchaseRequestPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PurchaseRequestPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_policy(): void
    {
        $warehouse = Warehouse::factory()->create();
        $otherWarehouse = Warehouse::factory()->create();

        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('activeMembership')->andReturn(new WarehouseMembership);
        $user->shouldReceive('hasPermissionTo')->andReturn(true);
        $user->shouldReceive('activeWarehouse')->andReturn($warehouse);
        $user->id = 1;

        $pr = PurchaseRequest::factory()->create(['warehouse_id' => $warehouse->id]);
        $otherPr = PurchaseRequest::factory()->create(['warehouse_id' => $otherWarehouse->id]);

        $policy = new PurchaseRequestPolicy;

        $this->assertTrue($policy->view($user, $pr));
        $this->assertFalse($policy->view($user, $otherPr));
    }
}
