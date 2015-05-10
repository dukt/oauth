<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2015, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Craft;

class OauthVariable
{
    // Public Methods
    // =========================================================================

    public function getProvider($handle, $configuredOnly = true)
    {
        return craft()->oauth->getProvider($handle, $configuredOnly);
    }

    public function getProviders($configuredOnly = true)
    {
        return craft()->oauth->getProviders($configuredOnly);
    }
}