<?php

namespace Mak8Tech\DpoPayments\Console\Commands;

use Illuminate\Console\Command;
use Mak8Tech\DpoPayments\Services\DpoService;
use Mak8Tech\DpoPayments\Services\SubscriptionService;

class DpoStatusCommand extends Command
{
    protected $signature = 'dpo:status {--process-subscriptions : Process due subscriptions}';
    protected $description = 'Check DPO integration status and process subscriptions';

    protected DpoService $dpoService;
    protected SubscriptionService $subscriptionService;

    public function __construct(DpoService $dpoService, SubscriptionService $subscriptionService)
    {
        parent::__construct();
        $this->dpoService = $dpoService;
        $this->subscriptionService = $subscriptionService;
    }

    public function handle()
    {
        $this->info('🔍 Checking DPO Payment Gateway Status...');

        // Check configuration
        $this->checkConfiguration();

        // Test API connection
        $this->testApiConnection();

        // Process subscriptions if requested
        if ($this->option('process-subscriptions')) {
            $this->processSubscriptions();
        }

        $this->info('✅ DPO status check complete!');
    }

    protected function checkConfiguration()
    {
        $this->info('📋 Configuration:');

        $config = [
            'Company Token' => config('dpo.company_token') ? '✓ Set' : '✗ Missing',
            'Service Type' => config('dpo.service_type'),
            'Test Mode' => config('dpo.test_mode') ? 'Enabled' : 'Disabled',
            'Default Country' => config('dpo.default_country'),
            'Default Currency' => config('dpo.default_currency'),
        ];

        foreach ($config as $key => $value) {
            $this->line("  {$key}: {$value}");
        }
    }

    protected function testApiConnection()
    {
        $this->info('🔌 Testing API Connection...');

        try {
            $balance = $this->dpoService->getBalance();
            $this->info("  ✓ API connection successful");
            $this->line("  Balance: {$balance['currency']} {$balance['balance']}");
        } catch (\Exception $e) {
            $this->error("  ✗ API connection failed: {$e->getMessage()}");
        }
    }

    protected function processSubscriptions()
    {
        $this->info('⚙️ Processing Due Subscriptions...');

        $results = $this->subscriptionService->processDueSubscriptions();

        $this->line("  Processed: {$results['processed']}");
        $this->line("  Successful: {$results['successful']}");
        $this->line("  Failed: {$results['failed']}");

        if (!empty($results['errors'])) {
            $this->error('  Errors:');
            foreach ($results['errors'] as $error) {
                $this->line("    - {$error['subscription']}: {$error['error']}");
            }
        }
    }
}
