<?php

namespace Tests\Feature\Procurement;

use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequestModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_determine_terminal_status(): void
    {
        $pr = PurchaseRequest::factory()->make(['status' => PurchaseRequestStatus::Draft]);
        $this->assertFalse($pr->isTerminal());
        $this->assertTrue($pr->isInProgress());

        $pr->status = PurchaseRequestStatus::Approved;
        $this->assertFalse($pr->isTerminal());
        $this->assertTrue($pr->isInProgress());

        $pr->status = PurchaseRequestStatus::Rejected;
        $this->assertTrue($pr->isTerminal());
        $this->assertFalse($pr->isInProgress());

        $pr->status = PurchaseRequestStatus::Completed;
        $this->assertTrue($pr->isTerminal());
        $this->assertFalse($pr->isInProgress());

        $pr->status = PurchaseRequestStatus::Cancelled;
        $this->assertTrue($pr->isTerminal());
        $this->assertFalse($pr->isInProgress());
    }
}
