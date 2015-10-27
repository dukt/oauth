<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2015, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Dukt\OAuth\Providers;

use Craft\UrlHelper;
use Dukt\OAuth\base\Provider;

class Google extends Provider
{
    // Properties
    // =========================================================================

	public $consoleUrl = 'https://code.google.com/apis/console/';
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

    public function getScopeDocsUrl()
    {
        return 'https://developers.google.com/identity/protocols/googlescopes';
    }

    public function getIconUrl()
    {
        return UrlHelper::getResourceUrl('oauth/icons/google.svg');
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
}