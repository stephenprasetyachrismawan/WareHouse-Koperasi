<?php

namespace Tests\Feature\Returns;

use App\Enums\ReturnStatus;
use App\Enums\WarehouseRole;
use App\Livewire\Returns\ApprovalQueue;
use App\Livewire\Returns\Show;
use App\Models\Item;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReturnApprovalLivewireTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private User $kepalaGudang;

    private ReturnRequest $returnRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::factory()->create();
        $this->kepalaGudang = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $this->kepalaGudang->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
        ]);

        $item = Item::factory()->create(['warehouse_id' => $this->warehouse->id]);
        $this->returnRequest = ReturnRequest::factory()->waitingApproval()->create(['warehouse_id' => $this->warehouse->id]);
        ReturnRequestItem::factory()->create([
            'return_request_id' => $this->returnRequest->id,
            'item_id' => $item->id,
        ]);
    }

    public function test_approval_queue_lists_waiting_approval_returns_for_the_warehouse(): void
    {
        Livewire::actingAs($this->kepalaGudang)
            ->test(ApprovalQueue::class)
            ->assertSee($this->returnRequest->return_number);
    }

    public function test_head_can_approve_from_the_show_component(): void
    {
        Livewire::actingAs($this->kepalaGudang)
            ->test(Show::class, ['returnRequest' => $this->returnRequest])
            ->call('approve')
            ->assertHasNoErrors();

        $this->assertEquals(ReturnStatus::ReplacementPending, $this->returnRequest->fresh()->status);
    }

    public function test_head_can_reject_from_the_show_component_with_a_reason(): void
    {
        Livewire::actingAs($this->kepalaGudang)
            ->test(Show::class, ['returnRequest' => $this->returnRequest])
            ->set('rejectReason', 'Bukti tidak meyakinkan.')
            ->call('reject')
            ->assertHasNoErrors();

        $fresh = $this->returnRequest->fresh();
        $this->assertEquals(ReturnStatus::Rejected, $fresh->status);
        $this->assertEquals('Bukti tidak meyakinkan.', $fresh->decision_notes);
    }

    public function test_reject_without_a_reason_fails_validation(): void
    {
        Livewire::actingAs($this->kepalaGudang)
            ->test(Show::class, ['returnRequest' => $this->returnRequest])
            ->set('rejectReason', '')
            ->call('reject')
            ->assertHasErrors(['rejectReason']);

        $this->assertEquals(ReturnStatus::WaitingApproval, $this->returnRequest->fresh()->status);
    }
}
