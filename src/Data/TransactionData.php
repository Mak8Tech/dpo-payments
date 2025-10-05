<?php

namespace Mak8Tech\DpoPayments\Data;

class TransactionData
{
    public function __construct(
        public float $amount,
        public string $currency,
        public string $reference,
        public string $description,
        public ?string $customerEmail = null,
        public ?string $customerName = null,
        public ?string $customerPhone = null,
        public ?string $customerCountry = null,
        public array $services = [],
        public bool $isRecurring = false
    ) {}

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'reference' => $this->reference,
            'description' => $this->description,
            'customer_email' => $this->customerEmail,
            'customer_name' => $this->customerName,
            'customer_phone' => $this->customerPhone,
            'customer_country' => $this->customerCountry,
            'services' => $this->services,
            'is_recurring' => $this->isRecurring,
        ];
    }
}
