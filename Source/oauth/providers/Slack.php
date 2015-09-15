<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2015, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Dukt\OAuth\Providers;

use Dukt\OAuth\base\Provider;

class Slack extends Provider
{
    // Properties
    // =========================================================================

    public $consoleUrl = 'https://api.slack.com/applications';
    public $oauthVersion = 2;

    // Public Methods
    // =========================================================================

    /* default scopes (minimum scope for getting user details) */

    /**
     * Get Name
     *
     * @return string
     */
    public function getName()
    {
        return 'Slack';
    }

    public function getAuthorizationMethod()
    {
        return 'oauth2_token';
    }

    /**
     * Create Slack Provider
     *
     * @return Slack
     */
    public function createProvider()
    {
        $config = array(
            'clientId' => $this->providerInfos->clientId,
            'clientSecret' => $this->providerInfos->clientSecret,
            'redirectUri' => $this->getRedirectUri(),
        );

        return new \Dukt\OAuth\OAuth2\Client\Provider\Slack($config);
    }
}
