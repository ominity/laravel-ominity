<?php

namespace Ominity\Laravel;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

/**
 * Class OminityOAuthProvider.
 */
class OminityOAuthProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The base url to the Ominity API.
     *
     * @const string
     */
    const OMINITY_API_URL = 'https://api.ominity.com';

    /**
     * The base url to the Ominity web application.
     *
     * @const string
     */
    const OMINITY_WEB_URL = 'https://app.ominity.com';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['me.read'];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * Get the authentication URL for the provider.
     *
     * @param  string  $state
     * @return string
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(env('OMINITY_WEB_ENDPOINT', static::OMINITY_WEB_URL).'/oauth2/authorize', $state);
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl()
    {
        return env('OMINITY_API_ENDPOINT', static::OMINITY_API_URL).'/oauth2/tokens';
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param  string  $token
     * @return array
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(env('OMINITY_API_ENDPOINT', static::OMINITY_API_URL).'/v1/me', [
            'headers' => ['Authorization' => 'Bearer '.$token],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @return \Laravel\Socialite\AbstractUser
     */
    protected function mapUserToObject(array $user)
    {
        if ($user['resource'] == 'user') {
            return (new User)->setRaw($user)->map([
                'id' => $user['id'],
                'nickname' => $user['firstName'],
                'name' => $user['firstName'].' '.$user['lastName'],
                'email' => $user['email'],
                'avatar' => $user['avatar'],
            ]);
        } elseif ($user['resource'] == 'admin') {
            return (new User)->setRaw($user)->map([
                'id' => $user['id'],
                'nickname' => $user['name'],
                'name' => $user['name'],
                'email' => $user['email'],
                'avatar' => null,
            ]);
        }
    }
}
