<?php

namespace Mak8Tech\DpoPayments\View\Components;

use Illuminate\View\Component;
use Illuminate\Pagination\LengthAwarePaginator;

class TransactionTable extends Component
{
    public LengthAwarePaginator $transactions;
    public array $countries;

    public function __construct(LengthAwarePaginator $transactions)
    {
        $this->transactions = $transactions;
        $this->countries = app(\Mak8Tech\DpoPayments\Services\CountryService::class)->getAllCountries();
    }

    public function render()
    {
        return view('dpo::components.transaction-table');
    }
}
