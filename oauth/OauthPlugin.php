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

class OauthPlugin extends BasePlugin
{
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
        return '0.9.61';
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
            'oauth\/(?P<providerHandle>.*)/tokens' => 'oauth/_tokens',
            'oauth\/(?P<providerHandle>.*)/test' => array('action' => "oauth/test"),
            'oauth\/(?P<providerHandle>.*)' => array('action' => "oauth/provider"),
        );
    }

    /**
     * HTML Settings
     */
    public function getSettingsHtml()
    {
        if(craft()->request->getPath() == 'settings/plugins') {
            return true;
        }

        return craft()->templates->render('oauth/settings', array(
            'settings' => $this->getSettings()
        ));
    }
}
