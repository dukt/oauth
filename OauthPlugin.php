<?php


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

    // --------------------------------------------------------------------

    /**
     * Get Version
     */
    function getVersion()
    {
        return '0.9';
    }

    // --------------------------------------------------------------------

    /**
     * Get Developer
     */
    function getDeveloper()
    {
        return 'Dukt';
    }

    // --------------------------------------------------------------------

    /**
     * Get Developer URL
     */
    function getDeveloperUrl()
    {
        return 'http://dukt.net/';
    }

    // --------------------------------------------------------------------

    /**
     * Has CP Section
     */
    public function hasCpSection()
    {
        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Hook Register CP Routes
     */
    public function hookRegisterCpRoutes()
    {
        return array(
            'oauth\/settings\/(?P<serviceProviderClass>.*)' => 'oauth/_settings',
        );
    }
}