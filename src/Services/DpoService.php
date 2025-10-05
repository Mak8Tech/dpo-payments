<?php

namespace Mak8Tech\DpoPayments\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Mak8Tech\DpoPayments\Exceptions\DpoException;
use Mak8Tech\DpoPayments\Data\TransactionData;
use Mak8Tech\DpoPayments\Data\TokenResponse;
use SimpleXMLElement;

class DpoService
{
    protected Client $client;
    protected string $companyToken;
    protected string $serviceType;
    protected bool $testMode;
    protected string $apiUrl;

    public function __construct(string $companyToken, string $serviceType, bool $testMode = false)
    {
        $this->companyToken = $companyToken;
        $this->serviceType = $serviceType;
        $this->testMode = $testMode;
        $this->apiUrl = $testMode
            ? config('dpo.test_api_url')
            : config('dpo.api_url');

        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'timeout' => 30,
            'verify' => !$testMode, // Disable SSL verification in test mode
            'headers' => [
                'Accept' => 'application/xml',
                'Content-Type' => 'application/xml',
            ],
        ]);
    }

    /**
     * Create a payment token
     */
    public function createToken(TransactionData $transaction): TokenResponse
    {
        $xml = $this->buildCreateTokenXml($transaction);

        try {
            $response = $this->client->post('/API/v6/', [
                'body' => $xml,
            ]);

            $result = $this->parseXmlResponse($response->getBody());

            if ($result['Result'] !== '000') {
                throw new DpoException(
                    $result['ResultExplanation'] ?? 'Token creation failed',
                    (int) $result['Result']
                );
            }

            return new TokenResponse(
                token: $result['TransToken'],
                reference: $result['TransRef'],
                result: $result['Result'],
                explanation: $result['ResultExplanation'],
                paymentUrl: $this->buildPaymentUrl($result['TransToken'])
            );
        } catch (RequestException $e) {
            Log::error('DPO API Error', [
                'error' => $e->getMessage(),
                'transaction' => $transaction->toArray(),
            ]);
            throw new DpoException('Failed to create payment token: ' . $e->getMessage());
        }
    }

    /**
     * Verify a payment token
     */
    public function verifyToken(string $token, string $reference = null): array
    {
        $xml = $this->buildVerifyTokenXml($token, $reference);

        try {
            $response = $this->client->post('/API/v6/', [
                'body' => $xml,
            ]);

            $result = $this->parseXmlResponse($response->getBody());

            if ($result['Result'] !== '000') {
                throw new DpoException(
                    $result['ResultExplanation'] ?? 'Token verification failed',
                    (int) $result['Result']
                );
            }

            return $result;
        } catch (RequestException $e) {
            Log::error('DPO Verification Error', [
                'error' => $e->getMessage(),
                'token' => $token,
            ]);
            throw new DpoException('Failed to verify token: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a payment token
     */
    public function cancelToken(string $token, string $reference = null): bool
    {
        $xml = $this->buildCancelTokenXml($token, $reference);

        try {
            $response = $this->client->post('/API/v6/', [
                'body' => $xml,
            ]);

            $result = $this->parseXmlResponse($response->getBody());

            return $result['Result'] === '000';
        } catch (RequestException $e) {
            Log::error('DPO Cancel Error', [
                'error' => $e->getMessage(),
                'token' => $token,
            ]);
            return false;
        }
    }

    /**
     * Process a refund
     */
    public function refundToken(string $token, float $amount, string $reference = null, string $reason = null): array
    {
        $xml = $this->buildRefundTokenXml($token, $amount, $reference, $reason);

        try {
            $response = $this->client->post('/API/v6/', [
                'body' => $xml,
            ]);

            $result = $this->parseXmlResponse($response->getBody());

            if ($result['Result'] !== '000') {
                throw new DpoException(
                    $result['ResultExplanation'] ?? 'Refund failed',
                    (int) $result['Result']
                );
            }

            return $result;
        } catch (RequestException $e) {
            Log::error('DPO Refund Error', [
                'error' => $e->getMessage(),
                'token' => $token,
                'amount' => $amount,
            ]);
            throw new DpoException('Failed to process refund: ' . $e->getMessage());
        }
    }

    /**
     * Create a recurring payment subscription
     */
    public function createSubscription(array $data): array
    {
        $xml = $this->buildSubscriptionXml($data);

        try {
            $response = $this->client->post('/API/v6/', [
                'body' => $xml,
            ]);

            $result = $this->parseXmlResponse($response->getBody());

            if ($result['Result'] !== '000') {
                throw new DpoException(
                    $result['ResultExplanation'] ?? 'Subscription creation failed',
                    (int) $result['Result']
                );
            }

            return $result;
        } catch (RequestException $e) {
            Log::error('DPO Subscription Error', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw new DpoException('Failed to create subscription: ' . $e->getMessage());
        }
    }

    /**
     * Update a subscription
     */
    public function updateSubscription(string $subscriptionId, array $updates): bool
    {
        $xml = $this->buildUpdateSubscriptionXml($subscriptionId, $updates);

        try {
            $response = $this->client->post('/API/v6/', [
                'body' => $xml,
            ]);

            $result = $this->parseXmlResponse($response->getBody());

            return $result['Result'] === '000';
        } catch (RequestException $e) {
            Log::error('DPO Update Subscription Error', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId,
            ]);
            return false;
        }
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(string $subscriptionId): bool
    {
        $xml = $this->buildCancelSubscriptionXml($subscriptionId);

        try {
            $response = $this->client->post('/API/v6/', [
                'body' => $xml,
            ]);

            $result = $this->parseXmlResponse($response->getBody());

            return $result['Result'] === '000';
        } catch (RequestException $e) {
            Log::error('DPO Cancel Subscription Error', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId,
            ]);
            return false;
        }
    }

    /**
     * Get account balance for multi-currency
     */
    public function getBalance(string $currency = null): array
    {
        $cacheKey = 'dpo_balance_' . ($currency ?? 'all');

        if (config('dpo.cache.enabled')) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $xml = $this->buildBalanceXml($currency);

        try {
            $response = $this->client->post('/API/v6/', [
                'body' => $xml,
            ]);

            $result = $this->parseXmlResponse($response->getBody());

            if ($result['Result'] !== '000') {
                throw new DpoException(
                    $result['ResultExplanation'] ?? 'Failed to get balance',
                    (int) $result['Result']
                );
            }

            $balance = [
                'currency' => $result['Currency'] ?? $currency,
                'balance' => (float) ($result['Balance'] ?? 0),
                'available' => (float) ($result['Available'] ?? 0),
                'reserved' => (float) ($result['Reserved'] ?? 0),
            ];

            if (config('dpo.cache.enabled')) {
                Cache::put($cacheKey, $balance, config('dpo.cache.ttl'));
            }

            return $balance;
        } catch (RequestException $e) {
            Log::error('DPO Balance Error', [
                'error' => $e->getMessage(),
                'currency' => $currency,
            ]);
            throw new DpoException('Failed to get balance: ' . $e->getMessage());
        }
    }

    /**
     * Build payment URL
     */
    protected function buildPaymentUrl(string $token): string
    {
        return $this->apiUrl . '/payv2.php?ID=' . $token;
    }

    /**
     * Build create token XML
     */
    protected function buildCreateTokenXml(TransactionData $transaction): string
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><API3G></API3G>');

        $xml->addChild('CompanyToken', $this->companyToken);
        $xml->addChild('Request', 'createToken');

        // Transaction level
        $transactionNode = $xml->addChild('Transaction');
        $transactionNode->addChild('PaymentAmount', number_format($transaction->amount, 2, '.', ''));
        $transactionNode->addChild('PaymentCurrency', $transaction->currency);
        $transactionNode->addChild('CompanyRef', $transaction->reference);
        $transactionNode->addChild('RedirectURL', url(config('dpo.redirect_url')));
        $transactionNode->addChild('BackURL', url(config('dpo.back_url')));
        $transactionNode->addChild('CompanyRefUnique', '1');
        $transactionNode->addChild('PTL', config('dpo.payment_timeout'));

        if ($transaction->customerEmail) {
            $transactionNode->addChild('customerEmail', $transaction->customerEmail);
        }
        if ($transaction->customerPhone) {
            $transactionNode->addChild('customerPhone', $transaction->customerPhone);
        }
        if ($transaction->customerName) {
            $transactionNode->addChild('customerFirstName', explode(' ', $transaction->customerName)[0]);
            $lastName = implode(' ', array_slice(explode(' ', $transaction->customerName), 1));
            if ($lastName) {
                $transactionNode->addChild('customerLastName', $lastName);
            }
        }
        if ($transaction->customerCountry) {
            $transactionNode->addChild('customerCountry', $transaction->customerCountry);
        }

        // Services level
        $servicesNode = $xml->addChild('Services');
        foreach ($transaction->services as $service) {
            $serviceNode = $servicesNode->addChild('Service');
            $serviceNode->addChild('ServiceType', $this->serviceType);
            $serviceNode->addChild('ServiceDescription', $service['description']);
            $serviceNode->addChild('ServiceDate', $service['date'] ?? date('Y/m/d H:i'));
        }

        // Additional fields for recurring
        if ($transaction->isRecurring) {
            $additionalNode = $xml->addChild('Additional');
            $additionalNode->addChild('BlockPayment', 'N');
            $additionalNode->addChild('RecurringPayment', 'Y');
            if (config('dpo.recurring.immediate_charge')) {
                $additionalNode->addChild('ChargeImmediately', 'Y');
            }
        }

        return $xml->asXML();
    }

    /**
     * Build verify token XML
     */
    protected function buildVerifyTokenXml(string $token, string $reference = null): string
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><API3G></API3G>');

        $xml->addChild('CompanyToken', $this->companyToken);
        $xml->addChild('Request', 'verifyToken');
        $xml->addChild('TransactionToken', $token);

        if ($reference) {
            $xml->addChild('CompanyRef', $reference);
        }

        return $xml->asXML();
    }

    /**
     * Build cancel token XML
     */
    protected function buildCancelTokenXml(string $token, string $reference = null): string
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><API3G></API3G>');

        $xml->addChild('CompanyToken', $this->companyToken);
        $xml->addChild('Request', 'cancelToken');
        $xml->addChild('TransactionToken', $token);

        if ($reference) {
            $xml->addChild('CompanyRef', $reference);
        }

        return $xml->asXML();
    }

    /**
     * Build refund token XML
     */
    protected function buildRefundTokenXml(string $token, float $amount, string $reference = null, string $reason = null): string
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><API3G></API3G>');

        $xml->addChild('CompanyToken', $this->companyToken);
        $xml->addChild('Request', 'refundToken');
        $xml->addChild('TransactionToken', $token);
        $xml->addChild('refundAmount', number_format($amount, 2, '.', ''));

        if ($reference) {
            $xml->addChild('CompanyRef', $reference);
        }
        if ($reason) {
            $xml->addChild('refundDetails', $reason);
        }

        return $xml->asXML();
    }

    /**
     * Build subscription XML
     */
    protected function buildSubscriptionXml(array $data): string
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><API3G></API3G>');

        $xml->addChild('CompanyToken', $this->companyToken);
        $xml->addChild('Request', 'createRecurring');

        $subscription = $xml->addChild('Subscription');
        $subscription->addChild('SubscriptionAmount', number_format($data['amount'], 2, '.', ''));
        $subscription->addChild('SubscriptionCurrency', $data['currency']);
        $subscription->addChild('SubscriptionFrequency', $data['frequency'] ?? 'MONTHLY');
        $subscription->addChild('SubscriptionStartDate', $data['start_date']);

        if (isset($data['end_date'])) {
            $subscription->addChild('SubscriptionEndDate', $data['end_date']);
        }

        $subscription->addChild('customerEmail', $data['customer_email']);
        $subscription->addChild('customerFirstName', $data['customer_first_name']);
        $subscription->addChild('customerLastName', $data['customer_last_name']);

        return $xml->asXML();
    }

    /**
     * Build update subscription XML
     */
    protected function buildUpdateSubscriptionXml(string $subscriptionId, array $updates): string
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><API3G></API3G>');

        $xml->addChild('CompanyToken', $this->companyToken);
        $xml->addChild('Request', 'updateRecurring');
        $xml->addChild('SubscriptionID', $subscriptionId);

        foreach ($updates as $key => $value) {
            $xml->addChild($key, $value);
        }

        return $xml->asXML();
    }

    /**
     * Build cancel subscription XML
     */
    protected function buildCancelSubscriptionXml(string $subscriptionId): string
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><API3G></API3G>');

        $xml->addChild('CompanyToken', $this->companyToken);
        $xml->addChild('Request', 'cancelRecurring');
        $xml->addChild('SubscriptionID', $subscriptionId);

        return $xml->asXML();
    }

    /**
     * Build balance XML
     */
    protected function buildBalanceXml(string $currency = null): string
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><API3G></API3G>');

        $xml->addChild('CompanyToken', $this->companyToken);
        $xml->addChild('Request', 'getBalance');

        if ($currency) {
            $xml->addChild('Currency', $currency);
        }

        return $xml->asXML();
    }

    /**
     * Parse XML response
     */
    protected function parseXmlResponse(string $xmlString): array
    {
        $xml = simplexml_load_string($xmlString);
        $json = json_encode($xml);
        return json_decode($json, true);
    }
}
