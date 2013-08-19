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
    public function connect($providerClass, $scope = null, $namespace = null, $userMode = false)
    {
        return craft()->oauth->connect($providerClass, $scope, $namespace, $userMode);
    }

    // --------------------------------------------------------------------

    public function disconnect($namespace, $providerClass)
    {
        return craft()->oauth->disconnect($namespace, $providerClass);
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

    public function getProviders()
    {
        return craft()->oauth->getProviders();
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
