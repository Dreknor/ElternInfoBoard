<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class KeycloakService extends AbstractProvider implements ProviderInterface
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['openid', 'profile', 'email'];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state)
    {
        $url = $this->buildAuthUrlFromBase(
            $this->getKeycloakUrl('/protocol/openid-connect/auth'),
            $state
        );

        Log::info('Keycloak Auth URL', ['url' => $url]);

        return $url;
    }

    protected function getTokenUrl()
    {
        $url = $this->getKeycloakUrl('/protocol/openid-connect/token');

        Log::info('Keycloak Token URL', ['url' => $url]);

        return $url;
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            $this->getKeycloakUrl('/protocol/openid-connect/userinfo'),
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode($response->getBody(), true);
    }

    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['sub'] ?? null,
            'name' => $user['name'] ?? $user['preferred_username'] ?? null,
            'email' => $user['email'] ?? null,
            'nickname' => $user['preferred_username'] ?? null,
            'givenName' => $user['given_name'] ?? null,
            'sn' => $user['family_name'] ?? null,
        ]);
    }

    protected function getTokenFields($code)
    {
        return [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUrl,
            'code' => $code,
        ];
    }

    protected function getCodeFields($state = null)
    {
        $fields = parent::getCodeFields($state);

        return array_merge($fields, [
            'response_type' => 'code',
        ]);
    }

    /**
     * Get the Keycloak URL with realm.
     *
     * @param  string  $path
     * @return string
     */
    protected function getKeycloakUrl($path)
    {
        $baseUrl = rtrim($this->getConfig('base_url'), '/');
        $realm = $this->getConfig('realm', 'ucs');

        $url = $baseUrl . '/realms/' . $realm . $path;

        Log::debug('Keycloak URL constructed', [
            'base_url' => $baseUrl,
            'realm' => $realm,
            'path' => $path,
            'full_url' => $url,
        ]);

        return $url;
    }

    /**
     * Get a config value.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    protected function getConfig($key, $default = null)
    {
        return $this->config[$key] ?? config('services.keycloak.' . $key, $default);
    }
}
