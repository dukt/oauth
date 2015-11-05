<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2015, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Dukt\OAuth\Providers;

use Craft\IOauth_Provider;
use Craft\OauthHelper;
use Craft\Oauth_ProviderInfosModel;
use Craft\Oauth_TokenModel;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\RequestException;

/**
 * Provider is the base class for classes representing providers in terms of objects.
 *
 * @author Dukt <support@dukt.net>
 * @since 1.0
 */

abstract class BaseProvider implements IOauth_Provider {

    // Properties
    // =========================================================================

    protected $token;
    protected $params = array();
    protected $providerInfos;
    protected $provider;
    protected $httpBuildEncType = 1;

    // Public Methods
    // =========================================================================

    /**
     * Get Icon URL
     *
     * @return string
     */
    public function getIconUrl()
    {
        return null;
    }

    /**
     * Get Scope Docs URL
     *
     * @return string
     */
    public function getScopeDocsUrl()
    {
        return null;
    }

    public function getDefaultScope()
    {
        $provider = $this->getProvider();

        if($provider && method_exists($provider, 'getScopes'))
        {
            return $this->getProvider()->getScopes();
        }
    }

    /**
     * Get Guzzle Subscriber
     */
    public function getSubscriber(Oauth_TokenModel $token)
    {
        return $this->createSubscriber($token);
    }

    public function createSubscriber(Oauth_TokenModel $token)
    {
        $infos = $this->getInfos();

        switch($this->getOauthVersion())
        {
            case 1:
                return new \Guzzle\Plugin\Oauth\OauthPlugin(array(
                    'consumer_key' => $infos->clientId,
                    'consumer_secret' => $infos->clientSecret,
                    'token' => $token->accessToken,
                    'token_secret' => $token->secret
                ));

                break;

            case 2:

                return new \Dukt\OAuth\Guzzle\Plugin\Oauth2Plugin(array(
                    'consumer_key' => $infos->clientId,
                    'consumer_secret' => $infos->clientSecret,
                    // 'authorization_method' => $this->getAuthorizationMethod(),
                    'access_token' => $token->accessToken,
                ));

                break;
        }
    }

    /**
     * Checks if the provider is configured
     */
    public function isConfigured()
    {
        if(!empty($this->providerInfos->clientId))
        {
            return true;
        }

        return false;
    }

    /**
     * Get Account
     */
    public function getAccount($token)
    {
        $provider = $this->getProvider();

        $realToken = OauthHelper::getRealToken($token);

        $response = $provider->getUserDetails($realToken);

        return $response->getArrayCopy();
    }

    protected function fetchProviderData($url, array $headers = [])
    {
        $client = $this->getProvider()->getHttpClient();
        $client->setBaseUrl($url);

        if ($headers)
        {
            $client->setDefaultOption('headers', $headers);
        }

        $request = $client->get()->send();
        $response = $request->getBody();

        return $response;
    }

    public function getUserDetails()
    {
        $token = OauthHelper::getRealToken($this->token);

        return $this->getProvider()->getUserDetails($token);
    }

    /**
     * Get Redirect URI
     */
    public function getRedirectUri()
    {
        return OauthHelper::getSiteActionUrl('oauth/connect');
    }

    /**
     * Get Handle
     */
    public function getHandle()
    {
        $class = $this->getClass();

        $handle = strtolower($class);

        return $handle;
    }

    /**
     * Get provider class
     *
     * from : Dukt\OAuth\Providers\Dribbble
     * to : Dribbble
     */
    public function getClass()
    {
        $nsClass = get_class($this);

        $class = substr($nsClass, strrpos($nsClass, "\\") + 1);

        return $class;
    }

    /**
     * Get Tokens
     */
    public function getTokens()
    {
        return \Craft\craft()->oauth->getTokensByProvider($this->getHandle());
    }

    /**
     * Get Provider
     */
    public function getProvider()
    {
        if (!isset($this->provider))
        {
            $this->provider = $this->createProvider();
        }

        return $this->provider;
    }

    /**
     * Set Infos
     */
    public function setInfos(Oauth_ProviderInfosModel $provider)
    {
        $this->providerInfos = $provider;
    }

    /**
     * Get Infos
     */
    public function getInfos()
    {
        return $this->providerInfos;
    }
}