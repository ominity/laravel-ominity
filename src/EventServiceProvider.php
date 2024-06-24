<?php

namespace Ominity\Laravel;

use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Ominity\Laravel\Listeners\UnsetCustomerSession;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [

        Logout::class => [
            UnsetCustomerSession::class,
        ],

    ];
}
