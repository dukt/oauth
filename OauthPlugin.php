<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2015, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Craft;

require_once(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');

class OauthPlugin extends BasePlugin
{
    // Public Methods
    // =========================================================================

    /**
     * Get OAuth Providers
     */
    public function getOauthProviders()
    {
        return [
            'Dukt\OAuth\Providers\Facebook',
            'Dukt\OAuth\Providers\Google',
            'Dukt\OAuth\Providers\Twitter',
            'Dukt\OAuth\Providers\Vimeo'
        ];
    }

    /**
     * Get Name
     */
    function getName()
    {
        return Craft::t('OAuth');
    }

    /**
     * Get Version
     */
    function getVersion()
    {
        return '1.0.76';
    }

    /**
     * Get Schema Version
     *
     * @return string
     */
    public function getSchemaVersion()
    {
        return '1.0.0';
    }

    /**
     * Get Developer
     */
    function getDeveloper()
    {
        return 'Dukt';
    }

    /**
     * Get Developer URL
     */
    function getDeveloperUrl()
    {
        return 'https://dukt.net/';
    }

    /**
     * Has CP Section
     */
    public function hasCpSection()
    {
        if(craft()->config->get('showCpSection', 'oauth') === true)
        {
            return true;
        }

        return false;
    }

    /**
     * Hook Register CP Routes
     */
    public function registerCpRoutes()
    {
        return array(
            'oauth' => array('action' => "oauth/index"),
            'oauth/providers/(?P<handle>.*)/tokens' => ['action' => 'oauth/tokens/providerTokens'],
            'oauth/providers/(?P<handle>.*)' => array('action' => "oauth/providerInfos"),
            'oauth/console' => array('action' => "oauth/console/index"),
            'oauth/console/(?P<providerHandle>.*)' => array('action' => "oauth/console/provider"),
        );
    }

    /**
     * Get Settings URL
     */
    public function getSettingsUrl()
    {
        return 'oauth';
    }
}
