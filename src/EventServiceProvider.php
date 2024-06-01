<?php

namespace Ominity\Laravel;

use App\Listeners\UnsetCustomerSession;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [

        Logout::class => [
            UnsetCustomerSession::class
        ]
        
    ];
}