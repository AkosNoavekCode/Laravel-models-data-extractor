<?php

namespace tests;

use AkosNoavek\DataExtractor\Facades\DataExtractor;
use AkosNoavek\DataExtractor\Support\ServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Register the Service Provider
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    // Register the Facade Alias manually
    protected function getPackageAliases($app)
    {
        return [
            'DataExtractor' => DataExtractor::class,
        ];
    }
}
