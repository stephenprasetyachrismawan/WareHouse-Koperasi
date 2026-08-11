<?php

namespace Tests\Feature\Returns;

use App\Enums\ReturnStatus;
use App\Enums\WarehouseRole;
use App\Livewire\Returns\ReplacementTasksQueue;
use App\Livewire\Returns\Show;
use App\Models\Item;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReturnReplacementLivewireTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private Item $item;

    private User $staff;

    private ReturnRequest $returnRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::factory()->create();
        $this->item = Item::factory()->create(['warehouse_id' => $this->warehouse->id]);

        $this->staff = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $this->staff->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        $this->returnRequest = ReturnRequest::factory()->replacementPending()->create(['warehouse_id' => $this->warehouse->id]);
        ReturnRequestItem::factory()->create([
            'return_request_id' => $this->returnRequest->id,
            'item_id' => $this->item->id,
            'return_quantity' => 2,
        ]);
    }

    public function test_replacement_tasks_queue_lists_the_pending_return(): void
    {
        Livewire::actingAs($this->staff)
            ->test(ReplacementTasksQueue::class)
            ->assertSee($this->returnRequest->return_number);
    }

    public function test_staff_can_check_availability_from_the_show_component(): void
    {
        StockBalance::factory()->create(['warehouse_id' => $this->warehouse->id, 'item_id' => $this->item->id, 'quantity' => 10]);

        Livewire::actingAs($this->staff)
            ->test(Show::class, ['returnRequest' => $this->returnRequest])
            ->call('checkAvailability')
            ->assertHasNoErrors();

        $this->assertEquals(ReturnStatus::ReadyForRepickup, $this->returnRequest->fresh()->status);
    }

    public function test_staff_can_complete_repickup_once_ready(): void
    {
        StockBalance::factory()->create(['warehouse_id' => $this->warehouse->id, 'item_id' => $this->item->id, 'quantity' => 10]);

        $component = Livewire::actingAs($this->staff)
            ->test(Show::class, ['returnRequest' => $this->returnRequest])
            ->call('checkAvailability');

        $component->call('completeRepickup')->assertHasNoErrors();

        $this->assertEquals(ReturnStatus::Completed, $this->returnRequest->fresh()->status);
    }
}
