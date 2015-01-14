<?php

/**
 * Craft OAuth by Dukt
 *
 * @package   Craft OAuth
 * @author    Benjamin David
 * @copyright Copyright (c) 2015, Dukt
 * @link      https://dukt.net/craft/oauth/
 * @license   https://dukt.net/craft/oauth/docs/license
 */


namespace OAuthProviderSources;

use \Craft\Craft;
use \Craft\LogLevel;
use \Craft\Oauth_TokenRecord;
use \Craft\Oauth_TokenModel;
use \Craft\Oauth_ProviderInfosRecord;
use \Craft\Oauth_ProviderInfosModel;
use \Craft\UrlHelper;

use OAuth\Common\Storage\Session;
use OAuth\Common\Consumer\Credentials;

abstract class BaseOAuthProviderSource {

    public $class;

    // public $isConfigured = false;

    public $isConnected = false;

    protected $service = null;
    public $storage = null;

    public $token = null;
    public $provider = null;

    protected $scopes = array();

    public function __construct()
    {
        // storage
        $this->storage = new Session();
    }

    public function getTokens()
    {
        return \Craft\craft()->oauth->getTokensByProvider($this->getHandle());
    }

    public function initService()
    {
        $handle = $this->getHandle();
        $serviceFactory = new \OAuth\ServiceFactory();
        $callbackUrl = \Craft\craft()->oauth->callbackUrl($handle);

        if($this->provider)
        {
            $credentials = new Credentials(
                $this->provider->clientId,
                $this->provider->clientSecret,
                $callbackUrl
            );
        }
        else
        {
            $credentials = new Credentials(
                'client id not provided',
                'client secret not provided',
                $callbackUrl
            );
        }

        $this->service = $serviceFactory->createService($handle, $credentials, $this->storage, $this->scopes);
    }

    public function initProviderSource($clientId = null, $clientSecret = null)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public function getAuthorizationMethod()
    {
        return null;
    }

    public function getAuthorizationUri($params)
    {
        return $this->service->getAuthorizationUri($params);
    }

    public function hasRefreshToken()
    {
        return method_exists($this->service, 'refreshAccessToken');
    }

    public function requestAccessToken($code)
    {
        return $this->service->requestAccessToken($code);
    }

    public function refreshAccessToken($token)
    {
        return $this->service->refreshAccessToken($token);
    }

    public function getProvider()
    {
        return $this->provider;
    }

    public function setInfos(Oauth_ProviderInfosModel $provider)
    {
        // set provider
        $this->provider = $provider;

        // re-initialize service with new scope
        $this->initService();
    }

    public function getInfos()
    {
        return $this->provider;
    }

    public function setScopes(array $scopes)
    {
        // set scope
        $this->scopes = $scopes;

        // re-initialize service with new scope
        $this->initService();
    }

    public function getScopes()
    {
        return $this->scopes;
    }

    public function getParams()
    {
        return array();
    }

    public function getStorage()
    {
        if(!$this->storage)
        {
            $this->storage = new Session();
        }
    }

    public function retrieveAccessToken()
    {
        return $this->storage->retrieveAccessToken($this->getClass());
    }

    public function setToken(Oauth_TokenModel $token)
    {
        $this->token = $token;

    }

    public function getToken()
    {
        return $this->token;
    }

    public function getRealToken()
    {
        switch($this->oauthVersion)
        {
            case 1:
                $realToken = new \OAuth\OAuth1\Token\StdOAuth1Token;
                $realToken->setAccessToken($this->token->accessToken);
                $realToken->setAccessTokenSecret($this->token->secret);
                return $realToken;
                break;

            case 2:
                $realToken = new \OAuth\OAuth2\Token\StdOAuth2Token;
                $realToken->setAccessToken($this->token->accessToken);
                $realToken->setEndOfLife($this->token->endOfLife);
                $realToken->setRefreshToken($this->token->refreshToken);
                return $realToken;
                break;
        }
    }

    public function getHandle()
    {
        // from : \OAuthProviderSource\FacebookOAuthProviderSource
        // to : facebook

        $handle = get_class($this);

        $start = strlen("\\OAuthProviderSource\\");
        $end = - strlen('OAuthProviderSource');

        $handle = substr($handle, $start, $end);

        $handle = strtolower($handle);

        return $handle;
    }

    public function getClass()
    {
        // from : \OAuthProviderSource\FacebookOAuthProviderSource
        // to : Facebook

        $handle = get_class($this);

        $start = strlen("\\OAuthProviderSource\\");
        $end = - strlen('OAuthProviderSource');

        $handle = substr($handle, $start, $end);

        return $handle;
    }

    public function isConfigured()
    {
        if(!empty($this->provider->clientId))
        {
            return true;
        }

        return false;
    }

    /**
     * Get Guzzle Subscriber
     */
    public function getSubscriber()
    {
        $headers = array();
        $query = array();

        $provider = $this->getProvider();
        $realToken = $this->getRealToken();

        switch($this->oauthVersion)
        {
            case 1:
                $oauth = new \Guzzle\Plugin\Oauth\OauthPlugin(array(
                    'consumer_key'    => $provider->clientId,
                    'consumer_secret' => $provider->clientSecret,
                    'token'           => $realToken->getAccessToken(),
                    'token_secret'    => $realToken->getAccessTokenSecret()
                ));

                return $oauth;

                break;

            case 2:
                $config = array(
                    'consumer_key' => $provider->clientId,
                    'consumer_secret' => $provider->clientSecret,
                    'authorization_method' => $this->getAuthorizationMethod(),
                    'access_token' => $realToken->getAccessToken(),
                );

                $oauth = new \Dukt\Rest\Guzzle\Plugin\Oauth2Plugin($config);

                return $oauth;

                break;
        }
    }


    /**
     * Get Account (alias)
     *
     * @deprecated Deprecated in 1.0.
     * @return array
     */
    public function getAccount()
    {
        return $this->getUserDetails();
    }

}