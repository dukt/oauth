<?php

/**
 * Craft OAuth by Dukt
 *
 * @package   Craft OAuth
 * @author    Benjamin David
 * @copyright Copyright (c) 2014, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 * @link      https://dukt.net/craft/oauth/
 */

namespace Craft;

class OauthVariable
{
    public function getToken($providerHandle)
    {
        return craft()->oauth->getToken($providerHandle);
    }

    public function encodeToken($token)
    {
        return craft()->oauth->encodeToken($token);
    }
    public function decodeToken($token)
    {
        return craft()->oauth->decodeToken($token);
    }

    public function callbackUrl($handle)
    {
        return craft()->oauth->callbackUrl($handle);
    }

    public function connect($handle, $scopes = null, $namespace = null, $params = array())
    {
        return craft()->oauth->connect($handle, $scopes, $namespace, $params);
    }

    public function disconnect($handle, $namespace = null)
    {
        return craft()->oauth->disconnect($handle, $namespace);
    }

    public function getAccount($handle, $namespace = null)
    {
        return craft()->oauth->getAccount($handle, $namespace);
    }

    public function getProvider($handle, $configuredOnly = true)
    {
        return craft()->oauth->getProvider($handle, $configuredOnly);
    }

    public function getProviderSource($handle)
    {
        return craft()->oauth->getProviderSource($handle);
    }

    public function getProviders($configuredOnly = true)
    {
        return craft()->oauth->getProviders($configuredOnly);
    }

    public function pluginCheckUpdates($pluginHandle)
    {
        return craft()->oauth_plugin->checkUpdates($pluginHandle);
    }

    public function pluginUpdatePluginsUrl($plugins)
    {
        return craft()->oauth_plugin->pluginUpdatePluginsUrl($plugins);
    }
}
