<?php

namespace Ominity\Laravel;

use Illuminate\Contracts\Container\Container;
use Ominity\Api\OminityApiClient;
use Ominity\Laravel\Services\VatValidationService;

class OminityManager
{
    public function __construct(private Container $app)
    {
    }

    public function api(): OminityApiClient
    {
        return $this->app->make(OminityApiClient::class);
    }

    public function renderer(): OminityPageRenderer
    {
        return $this->app->make(OminityPageRenderer::class);
    }

    public function vatValidator(): VatValidationService
    {
        return $this->app->make(VatValidationService::class);
    }
}
