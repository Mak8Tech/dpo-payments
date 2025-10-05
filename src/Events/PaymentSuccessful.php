<?php

namespace Mak8Tech\DpoPayments\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Mak8Tech\DpoPayments\Models\Transaction;

class PaymentSuccessful
{
    use Dispatchable, SerializesModels;

    public Transaction $transaction;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
