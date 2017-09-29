<?php

namespace Craft;

class OauthVariable
{
    // Public Methods
    // =========================================================================

    /**
     * Get Provider
     */
    public function getProvider($handle, $configuredOnly = true)
    {
        return craft()->oauth->getProvider($handle, $configuredOnly);
    }

    /**
     * Get Providers
     */
    public function getProviders($configuredOnly = true)
    {
        return craft()->oauth->getProviders($configuredOnly);
    }

    /**
     * Get Plugin Settings
     */
    public function getPluginSettings()
    {
        $plugin = craft()->plugins->getPlugin('oauth');

        return $plugin->getSettings();
    }
}
