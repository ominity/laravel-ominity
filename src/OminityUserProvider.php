<?php

namespace Ominity\Laravel;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Ominity\Api\OminityApiClient;
use Ominity\Laravel\Models\User;

class OminityUserProvider implements UserProvider
{
    protected OminityApiClient $ominityApiClient;

    protected $clientId;

    protected $clientSecret;

    public function __construct(OminityApiClient $ominityApiClient, $clientId, $clientSecret)
    {
        $this->ominityApiClient = $ominityApiClient;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public function retrieveById($identifier)
    {
        try {
            $apiUser = $this->ominityApiClient->users->get($identifier);

            $user = new User($this->ominityApiClient);
            foreach (get_object_vars($apiUser) as $property => $value) {
                $user->$property = $value;
            }

            return $user;
        } catch (\Ominity\Api\Exceptions\ApiException $e) {
            return null;
        }
    }

    public function retrieveByToken($identifier, $token)
    {
        try {
            $clientClone = clone $this->ominityApiClient;
            $clientClone->setAccessToken($token);

            $apiUser = $clientClone->users->get($identifier);

            $user = new User($this->ominityApiClient);
            foreach (get_object_vars($apiUser) as $property => $value) {
                $user->$property = $value;
            }

            return $user;
        } catch (\Ominity\Api\Exceptions\ApiException $e) {
            return null;
        }
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        // Optional for API token authentication
    }

    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials['email']) || empty($credentials['password'])) {
            return null;
        }

        try {
            $response = $this->authenticateUser($credentials['email'], $credentials['password']);

            if (! isset($response->access_token)) {
                return null;
            }

            $apiUsers = $this->ominityApiClient->users->all([
                'filter' => [
                    'email' => $credentials['email'],
                ],
            ]);

            if (empty($apiUsers)) {
                return null;
            }

            $apiUser = $apiUsers[0];

            return $this->retrieveByToken($apiUser->id, $response->access_token);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return ! is_null($user);
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false)
    {
        // TODO
    }

    /**
     * @param  string  $scope
     * @return \stdClass
     *
     * @throws \Ominity\Api\Exceptions\ApiException
     */
    protected function authenticateUser(string $username, string $password, $scope = '*')
    {
        $endpoint = $this->ominityApiClient->getApiEndpoint().'/oauth2/token';

        $body = [
            'grant_type' => 'password',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'username' => $username,
            'password' => $password,
            'scope' => $scope,
        ];

        $response = $this->ominityApiClient->performHttpCallToFullUrl(OminityApiClient::HTTP_POST, $endpoint, @json_encode($body));

        return $response;
    }
}
