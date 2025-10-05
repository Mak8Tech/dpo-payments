<?php

namespace Mak8Tech\DpoPayments\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Mak8Tech\DpoPayments\Models\Subscription;

class SubscriptionCreated
{
    use Dispatchable, SerializesModels;

    public Subscription $subscription;

    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }
}
