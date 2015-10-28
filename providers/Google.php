<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2015, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Dukt\OAuth\Providers;

use Craft\UrlHelper;

class Google extends BaseProvider
{
    // Properties
    // =========================================================================

    public $oauthVersion = 2;

    // Public Methods
    // =========================================================================

    /* default scopes (minimum scope for getting user details) */

    protected $scopes = array(
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/userinfo.email',
    );

    /**
     * Get Name
     *
     * @return string
     */
    public function getName()
    {
        return 'Google';
    }

    /**
     * Get Icon URL
     *
     * @return string
     */
    public function getIconUrl()
    {
        return UrlHelper::getResourceUrl('oauth/icons/google.svg');
    }


    /**
     * Get Scope Docs URL
     *
     * @return string
     */
    public function getScopeDocsUrl()
    {
        return 'https://developers.google.com/identity/protocols/googlescopes';
    }

    /**
     * Get Params
     *
     * @return array
     */
    public function getParams()
    {
        return  array(
            'access_type' => 'offline',
            'approval_prompt' => 'force'
        );
    }

    /**
     * Create Google Provider
     *
     * @return Google
     */
    public function createProvider()
    {
        $config = [
            'clientId' => $this->providerInfos->clientId,
            'clientSecret' => $this->providerInfos->clientSecret,
            'redirectUri' => $this->getRedirectUri(),
        ];

        return new \Dukt\OAuth\OAuth2\Client\Provider\Google($config);
    }

    /**
     * Get API Manager URL
     *
     * @return string
     */
    public function getManagerUrl()
    {
        return 'https://code.google.com/apis/console/';
    }

}