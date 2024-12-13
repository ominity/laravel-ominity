<?php

namespace Ominity\Laravel\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Ominity\Laravel\Exceptions\MfaAuthenticationException;
use Ominity\Laravel\Exceptions\MfaAuthorizationException;
use Ominity\Laravel\Exceptions\MfaNotEnabledException;

class AuthenticateMfa
{
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws MfaNotEnabledException
     * @throws MfaNotValidatedException
     */
    public function handle($request, Closure $next, $attribute = null)
    {
        if (! $this->config['enabled']) {
            return $next($request);
        }

        /** @var \Ominity\Laravel\Models\User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException();
        }

        if (!$user->isMfaEnabled && $attribute === 'strict') {
            $this->unauthorized($request);
        }

        if ($user->isMfaEnabled && !$user->isMfaValidated()) {
            $this->unauthenticated($request);
        }

        return $next($request);
    }

    /**
     * Handle an unauthenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Ominity\Laravel\Exceptions\MfaAuthenticationException
     */
    protected function unauthenticated($request)
    {
        throw new MfaAuthenticationException(
            'Unauthenticated. Multi-factor authentication not validated.', $this->redirectTo($request)
        );
    }

    /**
     * Handle an unauthorized user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Ominity\Laravel\Exceptions\MfaAuthorizationException
     */
    protected function unauthorized($request)
    {
        throw new MfaAuthorizationException(
            'Unauthorized. Multi-factor authentication not enabled.', $this->redirectTo($request)
        );
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo(Request $request)
    {
        if (! $request->expectsJson()) {
            return route('mfa.validate');
        }
    }
}
