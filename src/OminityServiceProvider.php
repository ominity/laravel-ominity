<?php

namespace Ominity\Laravel;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Ominity\Api\OminityApiClient;
use Ominity\Laravel\Console\Commands\PreRenderPagesCommand;

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
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'ominity');

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/ominity.php' => config_path('ominity.php')]);

            $this->commands([
                PreRenderPagesCommand::class,
            ]);
        }

        Auth::provider('ominity', function ($app, array $config) {
            return new OminityUserProvider(
                $app->make(OminityApiClient::class),
                $config['client_id'],
                $config['client_secret']
            );
        });

        $this->extendSocialite();
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

                if ($app['config']['ominity.localization']) {
                    $client->setLanguage(app()->getLocale());
                }

                return $client;
            }
        );

        $this->app->singleton(OminityPageRenderer::class, function ($app) {
            return new OminityPageRenderer($app->make(OminityApiClient::class));
        });

        $this->app->singleton(OminityManager::class);
    }

    /**
     * Extend the Laravel Socialite factory class, if available.
     *
     * @return void
     */
    protected function extendSocialite()
    {
        if (interface_exists($socialiteFactoryClass = \Laravel\Socialite\Contracts\Factory::class)) {
            $socialite = $this->app->make($socialiteFactoryClass);

            $socialite->extend('ominity', function (Container $app) use ($socialite) {
                $config = $app['config']['services.ominity'];

                return $socialite->buildProvider(OminityOAuthProvider::class, $config);
            });
        }
    }
}
