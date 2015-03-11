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
        return '0.9.63';
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
            'oauth\/console' => array('action' => "oauth/console"),
            'oauth\/console/(?P<providerHandle>.*)' => array('action' => "oauth/consoleProvider"),
            'oauth\/(?P<providerHandle>.*)/tokens' => 'oauth/_tokens',
            'oauth\/(?P<handle>.*)' => array('action' => "oauth/providerInfos"),
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

        return craft()->templates->render('oauth/_redirect', array(
            'settings' => $this->getSettings()
        ));
    }
}
