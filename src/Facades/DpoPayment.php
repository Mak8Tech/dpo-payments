<?php

namespace Mak8Tech\DpoPayments\Facades;

use Illuminate\Support\Facades\Facade;

class DpoPayment extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'dpo-payment';
    }
}
