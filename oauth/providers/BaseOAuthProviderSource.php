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

    public function initStorage()
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

    public function getRedirectUri()
    {
        return 'fake redirect uri';
    }

    public function setToken(OAuth_TokenModel $token)
    {
        $this->initStorage();

        $realToken = $token->getRealToken();

        $this->storage->storeAccessToken($this->getClass(), $realToken);
        $this->initService();
    }

    public function setRealToken($realToken)
    {
        $this->initStorage();

        $this->storage->storeAccessToken($this->getClass(), $realToken);

        $this->initService();
    }

    public function getHandle()
    {
        // from : \OAuthProviderSource\FacebookOAuthProviderSource
        // to : Facebook

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

    public function setClient($clientId = null, $clientSecret = null)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public function request($path, $method = 'GET', $body = null, array $extraHeaders = array())
    {
        return $this->service->request('https://www.googleapis.com/oauth2/v1/userinfo');
    }

    public function initService($scopes = array())
    {
        $this->initStorage();

        // try {

            $handle = $this->getHandle();
            $serviceFactory = new \OAuth\ServiceFactory();

            $credentials = new Credentials(
                $this->clientId,
                $this->clientSecret,
                \Craft\craft()->oauth->callbackUrl($handle)
            );

            $this->service = $serviceFactory->createService($handle, $credentials, $this->storage, $scopes);
        // }
        // catch(\Exception $e)
        // {

        // }
    }
}