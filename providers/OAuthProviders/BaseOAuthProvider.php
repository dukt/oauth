<?php

namespace OAuthProviders;

abstract class BaseOAuthProvider {

	public $isConfigured = false;
	public $isConnected = false;

	public $record = null;

	public $providerSource = null;

	// --------------------------------------------------------------------

	public function __construct($token = null, $scope = null)
	{
		$this->providerSource = \Craft\craft()->oauth_providers->providerInstantiate($this->getClassHandle(), $token, $scope);
	}

	public function connect($token = null, $scope = null)
	{
		$this->providerSource = \Craft\craft()->oauth_providers->providerInstantiate($this->getClassHandle(), $token, $scope);

		if(!$token) {
			$this->providerSource = \Craft\craft()->oauth_providers->providerConnect($this->providerSource);
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

	public function token()
	{
		return $this->providerSource->token();
	}

	// --------------------------------------------------------------------

	public function getClassHandle()
	{

		$handle = get_class($this);
		$handle = substr($handle, 15, -13);

		return $handle;
	}

	// --------------------------------------------------------------------

	public function hasScope($scope, $namespace = null)
	{
		return \Craft\craft()->oauth_providers->providerIsConnected($this->getClassHandle(), $scope, $namespace);
	}

	// --------------------------------------------------------------------
}