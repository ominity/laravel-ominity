<?php

namespace Ominity\Laravel\Exceptions;

use Exception;

class MfaAuthenticationException extends Exception
{
    /**
     * The path the user should be redirected to.
     *
     * @var string|null
     */
    protected $redirectTo;

    /**
     * Create a new MFA authentication exception.
     * 
     * @param  string  $message
     * @param  string|null  $redirectTo
     * @return void
     */
    public function __construct($message = 'Unauthenticated. Multi-factor authentication not validated.', $redirectTo = null) {
        parent::__construct($message);

        $this->redirectTo = $redirectTo;
    }

    /**
     * Get the path the user should be redirected to.
     *
     * @return string|null
     */
    public function redirectTo()
    {
        return $this->redirectTo;
    }
}
