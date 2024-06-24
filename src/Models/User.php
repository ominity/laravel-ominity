<?php

namespace Ominity\Laravel\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Support\Facades\Session;
use Ominity\Api\Resources\Users\User as OminityUser;

class User extends OminityUser implements AuthenticatableContract
{
    use Authenticatable;

    protected $currentCustomer;

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
        Session::put('ominity_customer_account', $customerId);
        Session::save();
    }

    /**
     * Get the current customer from the session.
     *
     * @return \Ominity\Api\Resources\Commerce\CustomerUser|null
     */
    public function getCurrentCustomer()
    {
        if ($this->currentCustomer) {
            return $this->currentCustomer;
        }

        $customerId = Session::get('ominity_customer_account');

        if ($customerId) {
            try {
                $this->currentCustomer = $this->client->users->customers->getFor($this, $customerId);

                return $this->currentCustomer;
            } catch (\Ominity\Api\Exceptions\ApiException $e) {
            }
        }

        return null;
    }
}
