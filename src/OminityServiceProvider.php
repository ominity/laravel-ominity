<?php

namespace Ominity\Laravel;

use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Ominity\Api\OminityApiClient;
use Ominity\Laravel\Console\Commands\PreRenderPagesCommand;
use Ominity\Laravel\Http\Middleware\AuthenticateMfa;
use Ominity\Laravel\Providers\EventServiceProvider;
use Ominity\Laravel\Rules\PaymentMethodEnabled;
use Ominity\Laravel\Rules\PaymentMethodMandateSupport;
use Ominity\Laravel\Rules\VatNumber;
use Ominity\Laravel\Rules\VatNumberFormat;
use Ominity\Laravel\Services\OminityCartService;
use Ominity\Laravel\Services\OminityRouterService;
use Ominity\Laravel\Services\VatValidationService;

class OminityServiceProvider extends ServiceProvider
{
    const PACKAGE_VERSION = '1.2.0';

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'ominity');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'ominity');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/ominity.php' => config_path('ominity.php'),
                __DIR__.'/../resources/views' => resource_path('views/vendor/ominity'),
            ]);

            $this->publishes([
                __DIR__.'/../dist' => public_path('vendor/ominity'),
            ], ['ominity-assets', 'laravel-assets']);

            AboutCommand::add('Ominity Laravel', fn () => ['Version' => self::PACKAGE_VERSION]);

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

        Blade::componentNamespace('Ominity\\Laravel\\Views\\Components', 'ominity');

        if (class_exists(\Laravel\Socialite\Contracts\Factory::class)) {
            $this->extendSocialite();
        }

        $this->extendValidation();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ominity.php', 'ominity');

        $this->app->register(EventServiceProvider::class);
        $this->app->register(OminityFrontendServiceProvider::class);

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

        $this->app->singleton(OminityRouterService::class, function ($app) {
            return new OminityRouterService($app->make(OminityApiClient::class), $app['config']['ominity.routes']);
        });

        $this->app->singleton(VatValidationService::class, function ($app) {
            return new VatValidationService($app->make(OminityApiClient::class));
        });

        $this->app->singleton(OminityCartService::class, function ($app) {
            return new OminityCartService($app->make(OminityApiClient::class), $app['config']['ominity.cart']);
        });

        $this->app->singleton(AuthenticateMfa::class, function ($app) {
            return new AuthenticateMfa($app['config']['ominity.users.mfa']);
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

    /**
     * Extend the Laravel Validation factory.
     *
     * @return void
     */
    protected function extendValidation()
    {
        $this->app->resolving(VatNumber::class, function ($rule, $app) {
            return new VatNumber($app->make(VatValidationService::class));
        });

        Validator::extend('vat_number', function ($attribute, $value, $parameters, $validator) {
            $vatValidationService = app(VatValidationService::class);
            $vatNumberRule = new VatNumber($vatValidationService);

            $fail = function ($message) use ($attribute, $validator) {
                $validator->errors()->add($attribute, $message);
            };

            $vatNumberRule->validate($attribute, $value, $fail);

            return ! $validator->errors()->has($attribute);
        });

        $this->app->resolving(VatNumberFormat::class, function ($rule, $app) {
            return new VatNumberFormat($app->make(VatValidationService::class));
        });

        Validator::extend('vat_number_format', function ($attribute, $value, $parameters, $validator) {
            $vatValidationService = app(VatValidationService::class);
            $vatNumberRule = new VatNumberFormat($vatValidationService);

            $fail = function ($message) use ($attribute, $validator) {
                $validator->errors()->add($attribute, $message);
            };

            $vatNumberRule->validate($attribute, $value, $fail);

            return ! $validator->errors()->has($attribute);
        });

        $this->app->resolving(PaymentMethodEnabled::class, function ($rule, $app) {
            return new PaymentMethodEnabled($app->make(OminityApiClient::class));
        });

        Validator::extend('paymentmethod_enabled', function ($attribute, $value, $parameters, $validator) {
            $ominityApiClient = app(OminityApiClient::class);
            $paymentmethodRule = new PaymentMethodEnabled($ominityApiClient);

            $fail = function ($message) use ($attribute, $validator) {
                $validator->errors()->add($attribute, $message);
            };

            $paymentmethodRule->validate($attribute, $value, $fail);

            return ! $validator->errors()->has($attribute);
        });

        $this->app->resolving(PaymentMethodMandateSupport::class, function ($rule, $app) {
            return new PaymentMethodMandateSupport($app->make(OminityApiClient::class));
        });

        Validator::extend('paymentmethod_mandate_support', function ($attribute, $value, $parameters, $validator) {
            $ominityApiClient = app(OminityApiClient::class);
            $paymentmethodRule = new PaymentMethodMandateSupport($ominityApiClient);

            $fail = function ($message) use ($attribute, $validator) {
                $validator->errors()->add($attribute, $message);
            };

            $paymentmethodRule->validate($attribute, $value, $fail);

            return ! $validator->errors()->has($attribute);
        });
    }
}
