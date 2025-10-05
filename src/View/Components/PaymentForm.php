<?php

namespace Mak8Tech\DpoPayments\View\Components;

use Illuminate\View\Component;
use Mak8Tech\DpoPayments\Services\CountryService;

class PaymentForm extends Component
{
    public float $amount;
    public string $defaultCountry;
    public string $defaultCurrency;
    public array $countries;
    public string $currencySymbol;
    
    public function __construct(
        float $amount = 0,
        ?string $defaultCountry = null,
        ?string $defaultCurrency = null
    ) {
        $countryService = app(CountryService::class);
        
        $this->amount = $amount;
        $this->defaultCountry = $defaultCountry ?? config('dpo.default_country');
        $this->defaultCurrency = $defaultCurrency ?? config('dpo.default_currency');
        $this->countries = $countryService->getAllCountries();
        
        $currencyConfig = config("dpo.currencies.{$this->defaultCurrency}");
        $this->currencySymbol = $currencyConfig['symbol'] ?? $this->defaultCurrency;
    }
    
    public function render()
    {
        return view('dpo::components.payment-form');
    }
}
