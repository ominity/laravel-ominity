<?php

namespace Ominity\Laravel;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Ominity\Api\OminityApiClient;

class OminityServiceProvider extends ServiceProvider
{
    const PACKAGE_VERSION = '1.0.0';

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/ominity.php' => config_path('ominity.php')]);
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ominity.php', 'ominity');

        $this->app->singleton(
            OminityApiClient::class,
            function (Container $app) {
                $client = (new OminityApiClient(new OminityLaravelHttpClientAdapter))
                    ->addVersionString('OminityLaravel/'.self::PACKAGE_VERSION);

                if (! empty($apiKey = $app['config']['ominity.key'])) {
                    $client->setApiKey($apiKey);
                }

                if (! empty($apiEndpoint = $app['config']['ominity.endpoint'])) {
                    $client->setApiEndpoint($apiEndpoint);
                }

                return $client;
            }
        );

        $this->app->singleton(OminityManager::class);
    }
}