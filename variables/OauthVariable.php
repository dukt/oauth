<?php

namespace Craft;

require_once(CRAFT_PLUGINS_PATH.'connect/vendor/autoload.php');

use ReflectionClass;
use Symfony\Component\Finder\Finder;

class OauthVariable
{
    public function providerIsConfigured($provider)
    {
        return craft()->oauth->providerIsConfigured($provider);
    }

    public function run($namespace, $providerClass, $url) {
        return craft()->oauth->run($namespace, $providerClass, $url);
    }

    public function authenticate($namespace, $providerClass, $scope = null, $userToken = false)
    {
        return craft()->oauth->authenticate($namespace, $providerClass, $scope, $userToken);
    }

    public function deauthenticate($namespace, $providerClass)
    {
        return craft()->oauth->deauthenticate($namespace, $providerClass);
    }

    public function isAuthenticated($namespace, $providerClass, $user = NULL)
    {
        return craft()->oauth->isAuthenticated($namespace, $providerClass, $user);
    }

    public function getProviders()
    {
        return craft()->oauth->getProviders();
    }

    public function getServiceByProviderClass($providerClass)
    {
        return craft()->oauth->getServiceByProviderClass($providerClass);
    }

    public function outputToken($providerClass)
    {
        return craft()->oauth->outputToken($providerClass);
    }

    public function getTokens($namespace = null) {
        return craft()->oauth->getTokens($namespace);
    }

    public function getAccount($namespace, $providerClass) {
        return craft()->oauth->getAccount($namespace, $providerClass);
    }

    public function test()
    {
        return craft()->connect_userSession->test();
    }
}
