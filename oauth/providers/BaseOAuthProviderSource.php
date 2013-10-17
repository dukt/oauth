<?php

/**
 * Craft OAuth by Dukt
 *
 * @package   Craft OAuth
 * @author    Benjamin David
 * @copyright Copyright (c) 2013, Dukt
 * @license   http://dukt.net/craft/oauth/docs#license
 * @link      http://dukt.net/craft/oauth/
 */

namespace OAuthProviderSources;

use \Craft\Craft;
use \Craft\LogLevel;
use \Craft\Oauth_TokenRecord;
use \Craft\Oauth_ProviderRecord;
use \Craft\UrlHelper;

abstract class BaseOAuthProviderSource {

	public $isConfigured = false;
	public $isConnected = false;

	private $_providerSource = null;

	// --------------------------------------------------------------------

	public function __construct($token = null, $scope = null)
	{
		$this->_initProviderSource($token, $scope);
	}

	// --------------------------------------------------------------------

	public function connect($token = null, $scope = null)
	{
        if($scope) {
            $this->_initProviderSource(null, $scope);
        }

		if(!$token) {

            try {
                Craft::log(__METHOD__." : Provider processing", LogLevel::Info, true);

                $this->_providerSource->process(function($url, $token = null) {

                    if ($token) {
                        $_SESSION['token'] = base64_encode(serialize($token));
                    }

                    header("Location: {$url}");

                    exit;

                }, function() {
                    return unserialize(base64_decode($_SESSION['token']));
                });

            } catch(\Exception $e) {

                Craft::log(__METHOD__." : Provider process failed : ".$e->getMessage(), LogLevel::Error);

            }
		}

		if($this->_providerSource) {
			$this->isConnected = true;
		}
	}

	// --------------------------------------------------------------------

	public function getScope()
	{
		$scope = $this->_providerSource->__get('scope');

		return $scope;
	}

    public function setToken($token)
    {
        return $this->_providerSource->setToken($token);
    }
	// --------------------------------------------------------------------

	public function getAccount()
	{

        $token = $this->getToken();

        $key = 'oauth.'.$this->getHandle().'.'.md5($token->access_token).'.account';

        $account = \Craft\craft()->fileCache->get($key);

        if(!$account) {

            // refresh token if needed

            $this->tokenRefresh();


            // $account = $this->_providerSource->getUserInfo();

            // use guzzle in order to improve error handling

            $account = @$this->_providerSource->getUserInfo();

            if($account) {

                if($account['uid']) {
                    \Craft\craft()->fileCache->set($key, $account);
                } else {
                    $account = null;
                }
            }
        }

        return $account;
	}

	// --------------------------------------------------------------------

	public function getToken()
	{
		return $this->_providerSource->token();
	}

    // --------------------------------------------------------------------

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

	// --------------------------------------------------------------------

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

	// --------------------------------------------------------------------

    public function setClient($clientId = null, $clientSecret = null)
    {
        $this->_providerSource->client_id = $clientId;
        $this->_providerSource->client_secret = $clientSecret;

        $this->_initProviderSource();
    }

    // --------------------------------------------------------------------

    private function _initProviderSource($token = null, $scope = null, $callbackUrl = null)
    {
    	$providerHandle = $this->getHandle();

        // get provider record

        if(!$callbackUrl) {
            $callbackUrl = UrlHelper::getSiteUrl(
                \Craft\craft()->config->get('actionTrigger').'/oauth/public/connect',
                array('provider' => $providerHandle)
            );
        }


        // provider options

        $opts = array(
            'id' => 'x',
            'secret' => 'x',
            'redirect_url' => 'x'
        );

    	if($this->_providerSource) {

            if(!empty($this->_providerSource->client_id)) {

                $opts = array(
                    'id' => $this->_providerSource->client_id,
                    'secret' => $this->_providerSource->client_secret,
                    'redirect_url' => $callbackUrl
                );

                $this->isConfigured = true;
            }

    	}

        if($scope) {
            if(is_array($scope) && !empty($scope)) {
                $opts['scope'] = $scope;
            }
        }


        $class = "\\OAuth\\Provider\\{$this->getClass()}";

        $this->_providerSource = new $class($opts);

        if($token) {
            $this->_providerSource->setToken($token);

            $this->tokenRefresh();
        }
    }

    // --------------------------------------------------------------------

    public function tokenRefresh()
    {
        $difference = ($this->_providerSource->token->expires - time());

        // token expired : we need to refresh it

        if($difference < 1) {

            Craft::log(__METHOD__." : Refresh token ", LogLevel::Info, true);

            $encodedToken = base64_encode(serialize($this->_providerSource->token));

            $token = \Craft\craft()->oauth->getTokenEncoded($encodedToken);

            if(method_exists($this->_providerSource, 'access') && $this->_providerSource->token->refresh_token) {

                $accessToken = $this->_providerSource->access($this->_providerSource->token->refresh_token, array('grant_type' => 'refresh_token'));

                if(!$accessToken) {
                    Craft::log(__METHOD__." : Could not refresh token", LogLevel::Info, true);
                }


                // save token

                $this->_providerSource->token->access_token = $accessToken->access_token;
                $this->_providerSource->token->expires = $accessToken->expires;

                $token->token = base64_encode(serialize($this->_providerSource->token));

                if(\Craft\craft()->oauth->tokenSave($token)) {
                    Craft::log(__METHOD__." : Token saved", LogLevel::Info, true);
                }
            } else {
                Craft::log(__METHOD__." : Access method (for refresh) doesn't exists for this provider", LogLevel::Info, true);
            }
        }
    }

    // --------------------------------------------------------------------

    public function getClientId()
    {
        return $this->_providerSource->client_id;
    }

    // --------------------------------------------------------------------

    public function getClientSecret()
    {
        return $this->_providerSource->client_secret;
    }

    // --------------------------------------------------------------------

    public function getRedirectUri()
    {
        return $this->_providerSource->redirect_uri;
    }

    // --------------------------------------------------------------------
}