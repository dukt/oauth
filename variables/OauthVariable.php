<?php

namespace Craft;

// require_once(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');

// use ReflectionClass;
// use Symfony\Component\Finder\Finder;

class OauthVariable
{

    // --------------------------------------------------------------------

    public function connect($namespace, $providerClass, $scope = null, $userToken = false)
    {
        return craft()->oauth->connect($namespace, $providerClass, $scope, $userToken);
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

    public function providerIsConnected($namespace, $providerClass, $user = NULL)
    {
        return craft()->oauth->providerIsConnected($namespace, $providerClass, $user);
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

    public function getTokens($namespace = null)
    {
        return craft()->oauth->getTokens($namespace);
    }

    // --------------------------------------------------------------------

    public function getAccount($namespace, $providerClass)
    {
        return craft()->oauth->getAccount($namespace, $providerClass);
    }

    // --------------------------------------------------------------------
}
