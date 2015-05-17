<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2015, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Dukt\OAuth\Providers;

use Dukt\OAuth\base\Provider;

class Github extends Provider
{
    // Properties
    // =========================================================================

    public $consoleUrl = 'https://github.com/settings/applications/';
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
        return 'GitHub';
    }

    /**
     * Create Github Provider
     *
     * @return Github
     */
    public function createProvider()
    {
        $config = [
            'clientId' => $this->providerInfos->clientId,
            'clientSecret' => $this->providerInfos->clientSecret,
            'redirectUri' => $this->getRedirectUri(),
        ];

        return new \League\OAuth2\Client\Provider\Github($config);
    }
}