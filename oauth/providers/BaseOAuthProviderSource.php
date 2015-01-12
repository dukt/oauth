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
use \Craft\Oauth_ProviderRecord;
use \Craft\Oauth_ProviderModel;
use \Craft\Oauth_TokenModel;
use \Craft\UrlHelper;

use OAuth\Common\Storage\Session;
use OAuth\Common\Consumer\Credentials;

abstract class BaseOAuthProviderSource {

    public $class;
    public $isConfigured = false;
    public $isConnected = false;

    public $clientId = false;
    public $clientSecret = false;

    public $service = null;
    public $storage = null;
    public $token = null;
    public $provider = null;

    public function __construct()
    {
        $this->storage = new Session();
    }

    public function setProvider(Oauth_ProviderModel $provider)
    {
        $this->provider = $provider;
    }

    public function getScopes()
    {
        return array();
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
        return $this->service->client_id;
    }

    public function getClientSecret()
    {
        return $this->service->client_secret;
    }

    // public function setToken($token)
    // {
    //     $this->getStorage();

    //     $this->storage->storeAccessToken($this->getClass(), $token);

    //     // $this->initializeService();

    //     $this->token = $token;
    // }

    public function setToken(Oauth_TokenModel $token)
    {
        $this->token = $token;
    }

    public function getToken()
    {
        return $this->token;
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

    public function initProviderSource($clientId = null, $clientSecret = null)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public function initializeService($scopes = array())
    {
        $this->getStorage();

        // try {
            $handle = $this->getHandle();
            $serviceFactory = new \OAuth\ServiceFactory();
            $callbackUrl = \Craft\craft()->oauth->callbackUrl($handle);

            $credentials = new Credentials(
                $this->clientId,
                $this->clientSecret,
                $callbackUrl
            );

            if(!$scopes)
            {
                $scopes = array();
            }

            $this->service = $serviceFactory->createService($handle, $credentials, $this->storage, $scopes);
        // }
        // catch(\Exception $e)
        // {

        // }
    }

    // deprecated for 4.0

    public function getAccount()
    {
        if(method_exists($this, 'getUserDetails'))
        {
            return $this->getUserDetails();
        }

    }

    // public function request($path, $method = 'GET', $body = null, array $extraHeaders = array())
    // {
    //     return $this->service->request('https://www.googleapis.com/oauth2/v1/userinfo');
    // }

}