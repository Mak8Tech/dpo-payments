<?php

namespace Mak8Tech\DpoPayments\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Mak8Tech\DpoPayments\DpoPaymentServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            DpoPaymentServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database for testing
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        // Set DPO config
        $app['config']->set('dpo.company_token', 'test-token');
        $app['config']->set('dpo.test_mode', true);
        $app['config']->set('dpo.default_country', 'ZM');
        $app['config']->set('dpo.default_currency', 'ZMW');
    }
}
