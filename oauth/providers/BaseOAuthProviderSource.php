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
use \Craft\Oauth_TokenModel;
use \Craft\Oauth_TokenRecord;
use \Craft\Oauth_ProviderRecord;
use \Craft\UrlHelper;

use OAuth\OAuth2\Service\Google;
use OAuth\Common\Storage\Session;
use OAuth\Common\Consumer\Credentials;

abstract class BaseOAuthProviderSource {

    public $isConfigured = false;
    public $isConnected = false;

    public $clientId = false;
    public $clientSecret = false;

    public $service = null;
    public $storage = null;

    public function __construct($token = null, $scopes = array())
    {
        $this->storage = new Session();
        // $this->initService($token, $scopes);
    }

    public function connectToken($token) {
        $this->connect($token);
    }

    public function connectScope($scope) {
        $this->connect(null, $scope);
    }

    public function connect($token = null, $scope = null)
    {

        if($scope) {
            $this->initService(null, $scope);
        }

        if(!$token)
        {
            $couldConnect = $this->service->process(function($url, $token = null) {

                if ($token)
                {
                    $_SESSION['token'] = base64_encode(serialize($token));
                }

                header("Location: {$url}");

                exit;

            }, function() {
                return unserialize(base64_decode($_SESSION['token']));
            });

            if(!$couldConnect)
            {
                Craft::log("Could not connect provider", LogLevel::Error);
            }
        }


        if($this->service)
        {
            $this->isConnected = true;
        }
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
        // return $this->service->redirect_uri;
        return 'fake redirect uri';
    }

    public function getScope()
    {
        return $this->service->scopes;
    }

    public function getToken()
    {
        return $this->service->token();
    }

    public function setToken(OAuth_TokenModel $token)
    {
        $realToken = $token->getRealToken();

        $this->storage->storeAccessToken('Google', $realToken);
        $this->initService();
    }

    public function getAccount()
    {
        $response = $this->service->request('https://www.googleapis.com/oauth2/v1/userinfo');
        $response = json_decode($response, true);

        $account = array();

        $account['uid'] = $response['id'];
        $account['name'] = $response['name'];

        return $account;

        // die();

        // return array('test');
        // $token = $this->getToken();

        // if(!$token)
        // {
        //     return null;
        // }

        // $key = 'oauth.'.$this->getHandle().'.'.md5($token->access_token).'.account';

        // $account = null;

        // if(!$account)
        // {
        //     // refresh token if needed
        //     $this->tokenRefresh();

        //     // account

        //     $account = $this->service->getUserInfo();

        //     if(empty($account['uid']))
        //     {
        //         $account = null;
        //     }
        // }

        // return $account;
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
        //$this->initService();
    }

    public function request($path, $method = 'GET', $body = null, array $extraHeaders = array())
    {
        return $this->service->request('https://www.googleapis.com/oauth2/v1/userinfo');
    }

    public function initService($scopes = array())
    {
        try {
            //$this->storage->retrieveAccessToken('google');


            $handle = $this->getHandle();
            $serviceFactory = new \OAuth\ServiceFactory();

            $credentials = new Credentials(
                $this->clientId,
                $this->clientSecret,
                \Craft\craft()->oauth->callbackUrl($handle)
            );

            $this->service = $serviceFactory->createService($handle, $credentials, $this->storage, $scopes);
        }
        catch(\Exception $e)
        {

        }
    }

    public function tokenRefresh()
    {
        $difference = ($this->service->token->expires - time());

        // token expired : we need to refresh it

        if($difference < 1)
        {
            $encodedToken = base64_encode(serialize($this->service->token));

            $token = \Craft\craft()->oauth->getTokenEncoded($encodedToken);

            if(method_exists($this->service, 'access') && $this->service->token->refresh_token)
            {
                $accessToken = $this->service->access($this->service->token->refresh_token, array('grant_type' => 'refresh_token'));

                if(!$accessToken)
                {
                    Craft::log("Could not refresh token", LogLevel::Error);
                }


                // save token

                $this->service->token->access_token = $accessToken->access_token;
                $this->service->token->expires = $accessToken->expires;

                $token->token = base64_encode(serialize($this->service->token));

                \Craft\craft()->oauth->tokenSave($token);
            }
            else
            {
                Craft::log("Access method (for refresh) doesn't exists for this provider", LogLevel::Info);
            }
        }
    }


































    private function deprecatedinitService($token = null, $scope = null, $callbackUrl = null)
    {
        $providerHandle = $this->getHandle();

        // get provider record

        if(!$callbackUrl)
        {
            $callbackUrl = \Craft\craft()->oauth->callbackUrl($providerHandle);
        }

        // provider options

        $opts = array(
            'id' => 'x',
            'secret' => 'x',
            'redirect_url' => 'x'
        );

        if($this->service)
        {
            if(!empty($this->service->client_id))
            {
                $opts = array(
                    'id' => $this->service->client_id,
                    'secret' => $this->service->client_secret,
                    'redirect_url' => $callbackUrl
                );

                $this->isConfigured = true;
            }

        }

        if($scope)
        {
            if(is_array($scope) && !empty($scope))
            {
                $opts['scope'] = $scope;
            }
        }


        $class = "\\OAuth\\Provider\\{$this->getClass()}";

        $this->service = new $class($opts);

        if($token)
        {
            $this->service->setToken($token);
            $this->tokenRefresh();
        }
    }
}