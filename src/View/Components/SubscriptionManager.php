<?php

namespace Mak8Tech\DpoPayments\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Collection;

class SubscriptionManager extends Component
{
    public Collection $subscriptions;

    public function __construct(Collection $subscriptions)
    {
        $this->subscriptions = $subscriptions;
    }

    public function render()
    {
        return view('dpo::components.subscription-manager');
    }
}
