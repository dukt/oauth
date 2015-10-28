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
    public function getOAuthProviders()
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
        return false;
    }

    /**
     * Hook Register CP Routes
     */
    public function registerCpRoutes()
    {
        return array(
            'oauth\/console' => array('action' => "oauth/console/index"),
            'oauth\/console/(?P<providerHandle>.*)' => array('action' => "oauth/console/provider"),
            'oauth\/(?P<providerHandle>.*)/tokens' => 'oauth/_tokens',
            'oauth\/(?P<handle>.*)' => array('action' => "oauth/providerInfos"),
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
