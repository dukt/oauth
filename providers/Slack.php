<?php

namespace Dukt\OAuth\Providers;

use Craft\UrlHelper;
use Craft\Oauth_TokenModel;

class Slack extends BaseProvider
{
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

    /**
     * Get Icon URL
     *
     * @return string
     */
    public function getIconUrl()
    {
        return UrlHelper::getResourceUrl('oauth/providers/slack.svg');
    }

    /**
     * Get OAuth Version
     *
     * @return int
     */
    public function getOauthVersion()
    {
        return 2;
    }

    /**
     * Get API Manager URL
     *
     * @return string
     */
    public function getManagerUrl()
    {
        return 'https://api.slack.com/applications';
    }

    /**
     * Get Scope Docs URL
     *
     * @return string
     */
    public function getScopeDocsUrl()
    {
        return 'https://api.slack.com/docs/oauth#auth_scopes';
    }

    /**
     * Create Provider
     */
    public function createProvider()
    {
        $config = [
            'clientId' => $this->providerInfos->clientId,
            'clientSecret' => $this->providerInfos->clientSecret,
            'redirectUri' => $this->getRedirectUri(),
        ];

        return new \AdamPaterson\OAuth2\Client\Provider\Slack($config);
    }

    /**
     * Create Subscriber
     */
    public function createSubscriber(Oauth_TokenModel $token)
    {
        $infos = $this->getInfos();

        return new \Dukt\OAuth\Guzzle\Subscribers\Slack([
            'access_token' => $token->accessToken,
        ]);
    }
}
