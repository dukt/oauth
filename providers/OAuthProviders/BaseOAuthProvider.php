<?php

namespace OAuthProviders;

use \Craft\Craft;
use \Craft\LogLevel;
use \Craft\Oauth_TokenRecord;
use \Craft\Oauth_ProviderRecord;
use \Craft\UrlHelper;

abstract class BaseOAuthProvider {

	public $isConfigured = false;
	public $isConnected = false;

	public $record = null;

	public $providerSource = null;

	// --------------------------------------------------------------------

	public function __construct($token = null, $scope = null)
	{
		$this->init($token, $scope);
	}

	// --------------------------------------------------------------------

	public function init($token = null, $scope = null)
	{
		$this->_initProviderSource($token, $scope);
	}

	// --------------------------------------------------------------------

	public function connect($token = null, $scope = null)
	{
		$this->_initProviderSource($token, $scope);

		if(!$token) {

            try {
                Craft::log(__METHOD__." : Provider processing", LogLevel::Info, true);

                $this->providerSource->process(function($url, $token = null) {

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

		if($this->providerSource) {
			$this->isConnected = true;
		}
	}

	// --------------------------------------------------------------------

	public function getScope()
	{
		$scope = $this->providerSource->__get('scope');

		return $scope;
	}

	// --------------------------------------------------------------------

	public function getAccount()
	{
		return $this->providerSource->getUserInfo();
	}

	// --------------------------------------------------------------------

	public function getToken()
	{
		return $this->providerSource->token();
	}

	// --------------------------------------------------------------------

	public function getHandle()
	{

		$handle = get_class($this);
		$handle = substr($handle, 15, -13);

		return $handle;
	}

	// --------------------------------------------------------------------

	public function hasScope($scope, $namespace = null)
	{
        Craft::log(__METHOD__, LogLevel::Info, true);

        $criteriaConditions = 'provider=:provider';
        $criteriaParams = array(':provider' => $this->getHandle());

        if(!$namespace) {
            $userId = \Craft\craft()->userSession->user->id;

            $criteriaConditions .= ' AND userId=:userId';
            $criteriaParams[':userId'] = $userId;

        } else {
            $criteriaConditions .= ' AND namespace=:namespace';
            $criteriaParams[':namespace'] = $namespace;
        }

        $tokenRecord = Oauth_TokenRecord::model()->find($criteriaConditions, $criteriaParams);

        if($tokenRecord) {
            Craft::log(__METHOD__." : Token Record found", LogLevel::Info, true);

            // check scope (scopeIsEnough)

            return \Craft\craft()->oauth->scopeIsEnough($scope, $tokenRecord->scope);
        }

        Craft::log(__METHOD__." : Token Record not found", LogLevel::Info, true);

        return false;
	}

	// --------------------------------------------------------------------

    private function _providerConnect()
    {

    }

    private function _initProviderSource($token = null, $scope = null, $callbackUrl = null)
    {
    	$providerClass = $this->getHandle();

        // get provider record

        $providerRecord = $this->providerRecord($providerClass);


        if(!$callbackUrl) {
            $callbackUrl = UrlHelper::getSiteUrl(
                \Craft\craft()->config->get('actionTrigger').'/oauth/public/connect',
                array('provider' => $providerClass)
            );
        }

        // provider options

        if($providerRecord) {

        	if(!empty($providerRecord->clientId) && !empty($providerRecord->clientSecret)) {
        		$this->isConfigured = true;
        	}

            $opts = array(
                'id' => $providerRecord->clientId,
                'secret' => $providerRecord->clientSecret,
                'redirect_url' => $callbackUrl
            );
        } else {
            $opts = array(
                'id' => 'x',
                'secret' => 'x',
                'redirect_url' => 'x'
            );
        }

        if($scope) {
            if(is_array($scope) && !empty($scope)) {
                $opts['scope'] = $scope;
            }
        }


        $class = "\\OAuth\\Provider\\{$providerClass}";

        $this->providerSource = new $class($opts);

        if($token) {
            $this->providerSource->setToken($token);

            $this->tokenRefresh();
        }
    }


    public function providerRecord($providerClass)
    {
        $providerRecord = Oauth_ProviderRecord::model()->find(

            // conditions

            'providerClass=:provider',


            // params

            array(
                ':provider' => $providerClass
            )
        );

        if($providerRecord) {
            return $providerRecord;
        }

        return null;
    }

    public function tokenRefresh()
    {
        $difference = ($this->providerSource->token->expires - time());

        // token expired : we need to refresh it

        if($difference < 1) {

            Craft::log(__METHOD__." : Refresh token ", LogLevel::Info, true);

            $encodedToken = base64_encode(serialize($this->providerSource->token));

            $token = \Craft\craft()->oauth->getTokenEncoded($encodedToken);

            if(method_exists($this->providerSource, 'access') && $this->providerSource->token->refresh_token) {

                $accessToken = $this->providerSource->access($this->providerSource->token->refresh_token, array('grant_type' => 'refresh_token'));

                if(!$accessToken) {
                    Craft::log(__METHOD__." : Could not refresh token", LogLevel::Info, true);
                }


                // save token

                $this->providerSource->token->access_token = $accessToken->access_token;
                $this->providerSource->token->expires = $accessToken->expires;

                $token->token = base64_encode(serialize($this->providerSource->token));

                if(\Craft\craft()->oauth->tokenSave($token)) {
                    Craft::log(__METHOD__." : Token saved", LogLevel::Info, true);
                }
            } else {
                Craft::log(__METHOD__." : Access method (for refresh) doesn't exists for this provider", LogLevel::Info, true);
            }
        }
    }
}