<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2015, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Dukt\OAuth\Providers;

use Craft\UrlHelper;
use Dukt\OAuth\base\Provider;

class Vimeo extends Provider
{
    // Properties
    // =========================================================================

    public $consoleUrl = 'https://developer.vimeo.com/apps';
    public $oauthVersion = 2;

    // Public Methods
    // =========================================================================

    /**
     * Get Name
     *
     * @return string
     */
    public function getName()
    {
        return 'Vimeo';
    }

    public function getIconUrl()
    {
        return UrlHelper::getResourceUrl('oauth/icons/vimeo.svg');
    }

    /**
     * Get Scope Docs URL
     *
     * @return string
     */
    public function getScopeDocsUrl()
    {
        return 'https://developers.facebook.com/docs/facebook-login/permissions/v2.5';
    }

    /**
     * Create Vimeo Provider
     *
     * @return Vimeo
     */
    public function createProvider()
    {
        $config = [
            'clientId' => $this->providerInfos->clientId,
            'clientSecret' => $this->providerInfos->clientSecret,
            'redirectUri' => $this->getRedirectUri(),
        ];

        return new \Dukt\OAuth\OAuth2\Client\Provider\Vimeo($config);
    }
}