<?php

namespace Ominity\Laravel\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Session;

class ClearUserSession
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }
    
    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(Logout $event)
    {
        Session::forget('ominity_mfa_validated_at');
        Session::forget('ominity_customer_account');
        Session::save();
    }
}
