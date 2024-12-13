<?php

namespace Ominity\Laravel\Providers;

use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Ominity\Laravel\Listeners\ClearUserSession;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [

        Logout::class => [
            ClearUserSession::class,
        ],

    ];

    public function boot()
    {
        parent::boot();
    }
}
