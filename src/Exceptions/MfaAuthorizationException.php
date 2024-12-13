<?php

namespace Ominity\Laravel\Exceptions;

use Exception;

class MfaAuthorizationException extends Exception
{
    /**
     * The path the user should be redirected to.
     *
     * @var string|null
     */
    protected $redirectTo;

    /**
     * Create a new MFA authorization exception.
     *
     * @param  string  $message
     * @param  string|null  $redirectTo
     * @return void
     */
    public function __construct($message = 'Unauthorized. Multi-factor authentication not enabled.', $redirectTo = null)
    {
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
