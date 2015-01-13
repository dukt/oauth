<?php

/**
 * Craft OAuth by Dukt
 *
 * @package   Craft OAuth
 * @author    Benjamin David
 * @copyright Copyright (c) 2014, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 * @link      https://dukt.net/craft/oauth/
 */


namespace OAuthProviderSources;

use \Craft\Craft;
use \Craft\LogLevel;
use \Craft\Oauth_TokenRecord;
use \Craft\Oauth_TokenModel;
use \Craft\Oauth_ProviderRecord;
use \Craft\Oauth_ProviderModel;
use \Craft\UrlHelper;

use OAuth\Common\Storage\Session;
use OAuth\Common\Consumer\Credentials;

abstract class BaseOAuthProviderSource {

    public $class;

    // public $isConfigured = false;

    public $isConnected = false;

    public $clientId = false;
    public $clientSecret = false;

    public $service = null;
    public $storage = null;
    public $token = null;

    public $provider = null;

    protected $scopes = array();

    public function __construct()
    {
        // storage
        $this->storage = new Session();

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

    public function getProvider()
    {
        return $this->provider;
    }

    public function setProvider(Oauth_ProviderModel $provider)
    {
        // set provider
        $this->provider = $provider;

        // re-initialize service with new scope
        $this->initService();

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

    public function hasAccessToken()
    {
        return $this->storage->hasAccessToken($this->getClass());
    }

    public function retrieveAccessToken()
    {
        return $this->storage->retrieveAccessToken($this->getClass());
    }

    public function getClientId()
    {
        return $this->provider->clientId;
    }

    public function getClientSecret()
    {
        return $this->provider->clientSecret;
    }

    public function setToken(Oauth_TokenModel $token)
    {
        $this->token = $token;

    }


        // $this->getStorage();

        // $realToken = $this->getRealToken();

        // $this->storage->storeAccessToken($this->getClass(), $realToken);


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
                // $realToken->setRequestToken($this->token->requestToken);
                // $realToken->setRequestTokenSecret($this->token->requestTokenSecret);
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

    public function initProviderSource($clientId = null, $clientSecret = null)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

}