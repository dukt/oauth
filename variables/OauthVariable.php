<?php

namespace Craft;

// require_once(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');

// use ReflectionClass;
// use Symfony\Component\Finder\Finder;

class OauthVariable
{

    // --------------------------------------------------------------------

    // public function connect($namespace, $providerClass, $scope = null, $userToken = false)
    // {
    //     return craft()->oauth->connect($namespace, $providerClass, $scope, $userToken);
    // }
    public function connect($providerClass, $scope = null, $namespace = null)
    {
        return craft()->oauth->connectUrl($providerClass, $scope, $namespace);
    }

    // --------------------------------------------------------------------

    public function disconnect($providerClass, $namespace = null)
    {
        return craft()->oauth->disconnectUrl($providerClass, $namespace);
    }

    // --------------------------------------------------------------------

    public function providerIsConfigured($provider)
    {
        return craft()->oauth->providerIsConfigured($provider);
    }

    // --------------------------------------------------------------------

    public function providerIsConnected($providerClass, $scope = null, $namespace = null, $userMode = false)
    {
        return craft()->oauth->providerIsConnected($providerClass, $scope, $namespace, $userMode);
    }

    // --------------------------------------------------------------------

    public function providerCallbackUrl($providerClass)
    {
        return craft()->oauth->providerCallbackUrl($providerClass);
    }

    // --------------------------------------------------------------------

    public function getProviders($configuredOnly = true)
    {
        return craft()->oauth->getProviders($configuredOnly);
    }

    // --------------------------------------------------------------------

    public function getProvider($providerClass)
    {
        return craft()->oauth->getProvider($providerClass);
    }

    // --------------------------------------------------------------------

    public function getProviderLibrary($providerClass, $namespace = null , $userToken = false)
    {
        return craft()->oauth->getProviderLibrary($providerClass, $namespace, $userToken);
    }

    // --------------------------------------------------------------------

    public function getTokens($namespace = null)
    {
        return craft()->oauth->getTokens($namespace);
    }

    // --------------------------------------------------------------------

    public function getTokensByProvider($provider, $user = false)
    {
        return craft()->oauth->getTokensByProvider($provider, $user);
    }

    // --------------------------------------------------------------------

    public function getToken($encodedToken)
    {
        return craft()->oauth->getToken($encodedToken);
    }

    // --------------------------------------------------------------------

    public function getAccount($providerClass, $namespace = null, $userMode = false)
    {
        return craft()->oauth->getAccount($providerClass, $namespace, $userMode);
    }

    // --------------------------------------------------------------------

    public function providerInstantiate($providerClass, $callbackUrl = null, $token = null, $scope = null)
    {
        return craft()->oauth->providerInstantiate($providerClass, $callbackUrl, $token, $scope);
    }
    // --------------------------------------------------------------------
}
