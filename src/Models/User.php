<?php

namespace Ominity\Laravel\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Support\Facades\Session;
use Ominity\Api\Resources\Users\User as OminityUser;

class User extends OminityUser implements AuthenticatableContract
{
    use Authenticatable;

    public function getKeyName()
    {
        return 'id';
    }

    /**
     * Set the current customer in the session.
     *
     * @param  int  $customerId
     */
    public function setCurrentCustomer($customerId)
    {
        Session::put('current_customer_id', $customerId);
    }

    /**
     * Get the current customer from the session.
     *
     * @return \Ominity\Api\Resources\Commerce\CustomerUser|null
     */
    public function getCurrentCustomer()
    {
        $customerId = Session::get('current_customer_id');

        if ($customerId) {
            try {
                return $this->client->users->customers->getFor($this, $customerId);
            } catch (\Ominity\Api\Exceptions\ApiException $e) {
            }
        }

        return null;
    }
}
