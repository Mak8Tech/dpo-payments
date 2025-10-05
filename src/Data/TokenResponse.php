<?php

namespace Mak8Tech\DpoPayments\Data;

class TokenResponse
{
    public function __construct(
        public string $token,
        public string $reference,
        public string $result,
        public string $explanation,
        public string $paymentUrl
    ) {}

    public function isSuccessful(): bool
    {
        return $this->result === '000';
    }

    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'reference' => $this->reference,
            'result' => $this->result,
            'explanation' => $this->explanation,
            'payment_url' => $this->paymentUrl,
            'successful' => $this->isSuccessful(),
        ];
    }
}
