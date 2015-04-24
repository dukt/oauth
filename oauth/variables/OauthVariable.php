<?php

/**
 * Craft OAuth by Dukt
 *
 * @package   Craft OAuth
 * @author    Benjamin David
 * @copyright Copyright (c) 2015, Dukt
 * @link      https://dukt.net/craft/oauth/
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Craft;

class OauthVariable
{
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
