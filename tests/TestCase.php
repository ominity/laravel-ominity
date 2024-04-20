<?php

namespace Ominity\Laravel\Tests;

use Ominity\Laravel\OminityServiceProvider;

/**
 * This is the abstract test case class.
 */
abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        $providers = [OminityServiceProvider::class];

        return $providers;
    }
}
