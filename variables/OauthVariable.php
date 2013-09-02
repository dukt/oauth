<?php

namespace Craft;

class OauthVariable
{
    // --------------------------------------------------------------------

    public function connect($providerClass, $scope = null, $namespace = null)
    {
        return craft()->oauth->connect($providerClass, $scope, $namespace);
    }

    // --------------------------------------------------------------------

    public function disconnect($providerClass, $namespace = null)
    {
        return craft()->oauth->disconnect($providerClass, $namespace);
    }

    // --------------------------------------------------------------------

    public function callbackUrl($providerClass)
    {
        return craft()->oauth->callbackUrl($providerClass);
    }

    // --------------------------------------------------------------------

    public function getAccount($providerClass, $namespace = null)
    {
        return craft()->oauth->getAccount($providerClass, $namespace);
    }

    // --------------------------------------------------------------------

    public function getProvider($providerClass, $configuredOnly = true)
    {
        return craft()->oauth->getProvider($providerClass, $configuredOnly);
    }

    // --------------------------------------------------------------------

    public function getProviders($configuredOnly = true)
    {
        return craft()->oauth->getProviders($configuredOnly);
    }

    // --------------------------------------------------------------------

    public function getSystemToken($providerClass, $namespace)
    {
        return craft()->oauth->getSystemToken($namespace);
    }

    // --------------------------------------------------------------------

    public function getUserToken($providerClass, $userId = null)
    {
        return craft()->oauth->getUserToken($providerClass, $userId);
    }

    // --------------------------------------------------------------------

    public function getSystemTokens()
    {
        return craft()->oauth->getSystemTokens();
    }

    // --------------------------------------------------------------------

    public function getUserTokens($userId = null)
    {
        return craft()->oauth->getUserTokens($userId);
    }

    // --------------------------------------------------------------------
}
