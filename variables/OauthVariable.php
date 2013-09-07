<?php

namespace Craft;

class OauthVariable
{
    // --------------------------------------------------------------------

    public function callbackUrl($providerHandle)
    {
        return craft()->oauth->callbackUrl($providerHandle);
    }

    // --------------------------------------------------------------------

    public function connect($providerHandle, $scope = null, $namespace = null)
    {
        return craft()->oauth->connect($providerHandle, $scope, $namespace);
    }

    // --------------------------------------------------------------------

    public function disconnect($providerHandle, $namespace = null)
    {
        return craft()->oauth->disconnect($providerHandle, $namespace);
    }

    // --------------------------------------------------------------------

    public function getAccount($providerHandle, $namespace = null)
    {
        return craft()->oauth->getAccount($providerHandle, $namespace);
    }

    // --------------------------------------------------------------------

    public function getProvider($handle, $configuredOnly = true)
    {
        return craft()->oauth->getProvider($handle, $configuredOnly);
    }


    // --------------------------------------------------------------------

    public function getProviders($configuredOnly = true)
    {
        return craft()->oauth->getProviders($configuredOnly);
    }

    // --------------------------------------------------------------------

    public function getSystemToken($providerHandle, $namespace)
    {
        return craft()->oauth->getSystemToken($providerHandle, $namespace);
    }

    // --------------------------------------------------------------------

    public function getSystemTokens()
    {
        return craft()->oauth->getSystemTokens();
    }

    // --------------------------------------------------------------------

    public function getUserToken($providerHandle, $userId = null)
    {
        return craft()->oauth->getUserToken($providerHandle, $userId);
    }

    // --------------------------------------------------------------------

    public function getUserTokens($userId = null)
    {
        return craft()->oauth->getUserTokens($userId);
    }

    // --------------------------------------------------------------------
}
