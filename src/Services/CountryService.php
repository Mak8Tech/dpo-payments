<?php

namespace Mak8Tech\DpoPayments\Services;

use Illuminate\Support\Facades\Cache;

class CountryService
{
    /**
     * Get all supported countries
     */
    public function getAllCountries(): array
    {
        return Cache::remember('dpo_countries', config('dpo.cache.ttl'), function () {
            return config('dpo.countries', []);
        });
    }

    /**
     * Get country by code
     */
    public function getCountry(string $code): ?array
    {
        $countries = $this->getAllCountries();

        return $countries[$code] ?? null;
    }

    /**
     * Get countries that support recurring payments
     */
    public function getRecurringCountries(): array
    {
        return array_filter($this->getAllCountries(), function ($country) {
            return $country['supports_recurring'] ?? false;
        });
    }

    /**
     * Get currency for country
     */
    public function getCurrencyForCountry(string $countryCode): string
    {
        $country = $this->getCountry($countryCode);

        return $country['currency'] ?? config('dpo.default_currency');
    }

    /**
     * Get mobile providers for country
     */
    public function getMobileProviders(string $countryCode): array
    {
        $country = $this->getCountry($countryCode);

        return $country['mobile_providers'] ?? [];
    }

    /**
     * Format currency amount
     */
    public function formatCurrency(float $amount, string $currency): string
    {
        $currencyConfig = config("dpo.currencies.{$currency}", [
            'symbol' => $currency,
            'decimals' => 2,
        ]);

        return $currencyConfig['symbol'] . ' ' .
            number_format($amount, $currencyConfig['decimals']);
    }

    /**
     * Validate country code
     */
    public function isValidCountry(string $code): bool
    {
        return array_key_exists($code, $this->getAllCountries());
    }

    /**
     * Get VAT rate for country
     */
    public function getVatRate(string $countryCode): float
    {
        $country = $this->getCountry($countryCode);

        return $country['vat_rate'] ?? 0;
    }
}
