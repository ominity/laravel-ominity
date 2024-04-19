<?php

namespace Ominity\Laravel;

use Illuminate\Contracts\Container\Container;
use Ominity\Api\OminityApiClient;

class OminityManager
{
    public function __construct(private Container $app)
    {
    }

    public function api(): OminityApiClient
    {
        return $this->app->make(OminityApiClient::class);
    }
}