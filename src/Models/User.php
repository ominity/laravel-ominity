<?php

namespace Ominity\Laravel\Models;

use DateTime;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Support\Facades\Session;
use Ominity\Api\Resources\Users\User as OminityUser;

class User extends OminityUser implements AuthenticatableContract
{
    use Authenticatable;

    protected $currentCustomer;

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

    /**
     * Is the user validated with MFA.
     */
    public function isMfaValidated(): bool
    {
        return Session::has('ominity_mfa_validated_at');
    }

    /**
     * Validate the user with MFA.
     *
     * @param  string  $code
     * @param  string|null  $method
     * @return bool
     */
    public function validateMfaCode($code, $method): bool
    {
        try {
            $success = $this->client->users->mfa->validateForId($this->id, $method, [
                'code' => $code,
                'ipAddress' => request()->ip(),
                'userAgent' => request()->header('User-Agent'),
            ]);

            if($success) {
                $this->mfa_validated_at = now();

                Session::put('ominity_mfa_validated_at', $this->mfa_validated_at);
                Session::save();
            }

            return $success;
        }
        catch(\Exception $e) {
            return false;
        }
    }

    /**
     * Laravel Authenticatable methods
     * */
    public function getKeyName()
    {
        return 'id';
    }

    public function getRememberTokenName()
    {
        return null;
    }
}
