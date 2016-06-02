<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2016, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Craft;

class OauthPlugin extends BasePlugin
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        require_once(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');
        require_once(CRAFT_PLUGINS_PATH.'oauth/etc/providers/IOauth_Provider.php');
        require_once(CRAFT_PLUGINS_PATH.'oauth/providers/BaseProvider.php');
        
        parent::init();
    }
    /**
     * Get OAuth Providers
     */
    public function getOauthProviders()
    {
        require_once(CRAFT_PLUGINS_PATH.'oauth/providers/Facebook.php');
        require_once(CRAFT_PLUGINS_PATH.'oauth/providers/Google.php');
        require_once(CRAFT_PLUGINS_PATH.'oauth/providers/Twitter.php');
        require_once(CRAFT_PLUGINS_PATH.'oauth/providers/Vimeo.php');

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
     * Get Description
     */
    public function getDescription()
    {
        return Craft::t('Consume OAuth-based web services.');
    }
    
    /**
     * Get Version
     */
    public function getVersion()
    {
        return '2.0.0';
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
     * Get Documentation URL
     */
    public function getDocumentationUrl()
    {
        return 'https://dukt.net/craft/oauth/docs/';
    }

    /**
     * Get Release Feed URL
     */
    public function getReleaseFeedUrl()
    {
        return 'https://dukt.net/craft/oauth/updates.json';
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
            'oauth' => ['action' => "oauth/providers/index"],
            'oauth/settings' => ['action' => "oauth/settings/index"],

            'oauth/tokens' => ['action' => "oauth/tokens/index"],

            'oauth/providers' => ['action' => "oauth/providers/index"],
            'oauth/providers/(?P<handle>.*)/tokens' => ['action' => 'oauth/tokens/providerTokens'],
            'oauth/providers/(?P<handle>.*)' => ['action' => "oauth/providers/providerInfos"],

            'oauth/console' => ['action' => "oauth/console/index"],
            'oauth/console/(?P<providerHandle>.*)' => ['action' => "oauth/console/provider"],
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
