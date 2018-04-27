<?php

namespace Craft;

class OauthPlugin extends BasePlugin
{
    // Public Methods
    // =========================================================================

    /**
     * Get OAuth Providers
     */
    public function getOauthProviders()
    {
        require_once(CRAFT_PLUGINS_PATH.'oauth/providers/Facebook.php');
        require_once(CRAFT_PLUGINS_PATH.'oauth/providers/Github.php');
        require_once(CRAFT_PLUGINS_PATH.'oauth/providers/Google.php');
        require_once(CRAFT_PLUGINS_PATH.'oauth/providers/Instagram.php');
        require_once(CRAFT_PLUGINS_PATH.'oauth/providers/Linkedin.php');
        require_once(CRAFT_PLUGINS_PATH.'oauth/providers/Slack.php');
        require_once(CRAFT_PLUGINS_PATH.'oauth/providers/Twitter.php');
        require_once(CRAFT_PLUGINS_PATH.'oauth/providers/Vimeo.php');

        return [
            'Dukt\OAuth\Providers\Facebook',
            'Dukt\OAuth\Providers\Github',
            'Dukt\OAuth\Providers\Google',
            'Dukt\OAuth\Providers\Instagram',
            'Dukt\OAuth\Providers\Linkedin',
            'Dukt\OAuth\Providers\Slack',
            'Dukt\OAuth\Providers\Twitter',
            'Dukt\OAuth\Providers\Vimeo'
        ];
    }

    /**
     * Get Name
     */
    public function getName()
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
        return '2.1.4';
    }

    /**
     * Get Schema Version
     *
     * @return string
     */
    public function getSchemaVersion()
    {
        return '1.0.1';
    }

    /**
     * Get Developer
     */
    public function getDeveloper()
    {
        return 'Dukt';
    }

    /**
     * Get Developer URL
     */
    public function getDeveloperUrl()
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

    /**
     * Get release feed URL
     */
    public function getReleaseFeedUrl()
    {
        return 'https://raw.githubusercontent.com/dukt/oauth/v2/releases.json';
    }
}
