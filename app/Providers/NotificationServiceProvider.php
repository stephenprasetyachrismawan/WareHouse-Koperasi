<?php

namespace App\Providers;

use App\Domain\Pickup\Events\PickupRequestReadyForPickup;
use App\Domain\Pickup\Events\PickupRequestSubmitted;
use App\Domain\Procurement\Events\CancellationApproved;
use App\Domain\Procurement\Events\CancellationRejected;
use App\Domain\Procurement\Events\CancellationRequested;
use App\Domain\Procurement\Events\PurchaseOrderSent;
use App\Domain\Procurement\Events\PurchaseRequestApproved;
use App\Domain\Procurement\Events\PurchaseRequestRejected;
use App\Domain\Procurement\Events\PurchaseRequestSubmitted;
use App\Domain\Returns\Events\ReturnApproved;
use App\Domain\Returns\Events\ReturnReadyForRepickup;
use App\Domain\Returns\Events\ReturnRejected;
use App\Domain\Returns\Events\ReturnSubmitted;
use App\Listeners\Notifications\NotifyCancellationApproved;
use App\Listeners\Notifications\NotifyCancellationRejected;
use App\Listeners\Notifications\NotifyCancellationRequested;
use App\Listeners\Notifications\NotifyPickupReadyForPickup;
use App\Listeners\Notifications\NotifyPickupRequested;
use App\Listeners\Notifications\NotifyPurchaseOrderSent;
use App\Listeners\Notifications\NotifyPurchaseRequestApprovalRequired;
use App\Listeners\Notifications\NotifyPurchaseRequestApproved;
use App\Listeners\Notifications\NotifyPurchaseRequestRejected;
use App\Listeners\Notifications\NotifyReplacementReady;
use App\Listeners\Notifications\NotifyReturnApproved;
use App\Listeners\Notifications\NotifyReturnRejected;
use App\Listeners\Notifications\NotifyReturnSubmitted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the persistent inbox (Phase 6.1) to existing domain events. Kept
 * separate from AppServiceProvider so the growing notification-integration
 * list doesn't crowd unrelated app bootstrapping. Every listener here only
 * ever creates InboxNotification rows — it never mutates business state.
 */
class NotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(PurchaseRequestSubmitted::class, NotifyPurchaseRequestApprovalRequired::class);
        Event::listen(PurchaseRequestApproved::class, NotifyPurchaseRequestApproved::class);
        Event::listen(PurchaseRequestRejected::class, NotifyPurchaseRequestRejected::class);

        Event::listen(CancellationRequested::class, NotifyCancellationRequested::class);
        Event::listen(CancellationApproved::class, NotifyCancellationApproved::class);
        Event::listen(CancellationRejected::class, NotifyCancellationRejected::class);

        Event::listen(PickupRequestSubmitted::class, NotifyPickupRequested::class);
        Event::listen(PickupRequestReadyForPickup::class, NotifyPickupReadyForPickup::class);

        Event::listen(ReturnSubmitted::class, NotifyReturnSubmitted::class);
        Event::listen(ReturnApproved::class, NotifyReturnApproved::class);
        Event::listen(ReturnRejected::class, NotifyReturnRejected::class);
        Event::listen(ReturnReadyForRepickup::class, NotifyReplacementReady::class);

        Event::listen(PurchaseOrderSent::class, NotifyPurchaseOrderSent::class);
    }
}
