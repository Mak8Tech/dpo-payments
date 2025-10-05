<?php

namespace Mak8Tech\DpoPayments\Tests\Unit;

use Mak8Tech\DpoPayments\Services\DpoService;
use Mak8Tech\DpoPayments\Data\TransactionData;
use Mak8Tech\DpoPayments\Tests\TestCase;
use Mockery;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class DpoServiceTest extends TestCase
{
    protected DpoService $service;
    protected $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = Mockery::mock(Client::class);
        $this->service = new DpoService('test-token', '3854', true);

        // Inject mock client using reflection
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($this->service, $this->mockClient);
    }

    public function test_can_create_payment_token()
    {
        $xmlResponse = '<?xml version="1.0" encoding="UTF-8"?>
            <API3G>
                <Result>000</Result>
                <ResultExplanation>Success</ResultExplanation>
                <TransToken>TEST-TOKEN-123</TransToken>
                <TransRef>TEST-REF-123</TransRef>
            </API3G>';

        $this->mockClient->shouldReceive('post')
            ->once()
            ->andReturn(new Response(200, [], $xmlResponse));

        $transactionData = new TransactionData(
            amount: 100.00,
            currency: 'ZMW',
            reference: 'TEST-REF',
            description: 'Test Payment',
            customerEmail: 'test@example.com',
            customerName: 'Test User',
            customerPhone: '+260123456789',
            customerCountry: 'ZM',
            services: [['description' => 'Test Service']],
            isRecurring: false
        );

        $response = $this->service->createToken($transactionData);

        $this->assertEquals('TEST-TOKEN-123', $response->token);
        $this->assertEquals('TEST-REF-123', $response->reference);
        $this->assertTrue($response->isSuccessful());
    }

    public function test_can_verify_token()
    {
        $xmlResponse = '<?xml version="1.0" encoding="UTF-8"?>
            <API3G>
                <Result>000</Result>
                <ResultExplanation>Verified</ResultExplanation>
                <TransactionApproval>1</TransactionApproval>
            </API3G>';

        $this->mockClient->shouldReceive('post')
            ->once()
            ->andReturn(new Response(200, [], $xmlResponse));

        $result = $this->service->verifyToken('TEST-TOKEN-123');

        $this->assertEquals('000', $result['Result']);
        $this->assertEquals('Verified', $result['ResultExplanation']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
