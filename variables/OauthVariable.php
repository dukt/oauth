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

    public function connectCallback($providerClass)
    {
        return craft()->oauth->connectCallback($providerClass);
    }

    // --------------------------------------------------------------------

    public function providerInstantiate($providerClass, $callbackUrl = null, $token = null, $scope = null)
    {
        return craft()->oauth->providerInstantiate($providerClass, $callbackUrl, $token, $scope);
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

    public function getAccount($providerClass, $namespace = null, $userMode = false)
    {
        return craft()->oauth->getAccount($providerClass, $namespace, $userMode);
    }

    // --------------------------------------------------------------------

    public function getProvider($providerClass)
    {
        return craft()->oauth->getProvider($providerClass);
    }

    // --------------------------------------------------------------------

    public function getProviders($configuredOnly = true)
    {
        return craft()->oauth->getProviders($configuredOnly);
    }

    // --------------------------------------------------------------------

    public function getTokens($namespace = null)
    {
        return craft()->oauth->getTokens($namespace);
    }

    // --------------------------------------------------------------------

    public function tokensByProvider($provider, $user = false)
    {
        return craft()->oauth->tokensByProvider($provider, $user);
    }

    // --------------------------------------------------------------------
}
