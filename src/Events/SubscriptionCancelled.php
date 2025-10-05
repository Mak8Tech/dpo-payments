<?php

namespace Mak8Tech\DpoPayments\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Mak8Tech\DpoPayments\Models\Subscription;

class SubscriptionCancelled
{
    use Dispatchable, SerializesModels;

    public Subscription $subscription;
    public ?string $reason;

    public function __construct(Subscription $subscription, ?string $reason = null)
    {
        $this->subscription = $subscription;
        $this->reason = $reason;
    }
}
