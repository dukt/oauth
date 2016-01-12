<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2016, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Dukt\OAuth\Providers;

use Craft\UrlHelper;
use Craft\OauthHelper;

class Twitter extends BaseProvider
{
    // Public Methods
    // =========================================================================

    /**
     * Get Name
     *
     * @return string
     */
    public function getName()
    {
        return 'Twitter';
    }

    /**
     * Get Icon URL
     *
     * @return string
     */
    public function getIconUrl()
    {
        return UrlHelper::getResourceUrl('oauth/providers/twitter.svg');
    }

    /**
     * Get OAuth Version
     *
     * @return int
     */
    public function getOauthVersion()
    {
        return 1;
    }

    /**
     * Create Twitter Provider
     *
     * @return Twitter
     */
    public function createProvider()
    {
        if($this->providerInfos->clientId && $this->providerInfos->clientSecret)
        {
            $config = [
                'identifier' => $this->providerInfos->clientId,
                'secret' => $this->providerInfos->clientSecret,
                'callback_uri' => $this->getRedirectUri(),
            ];

            return new \League\OAuth1\Client\Server\Twitter($config);
        }
    }

    /**
     * Get API Manager URL
     *
     * @return string
     */
    public function getManagerUrl()
    {
        return 'https://dev.twitter.com/apps';
    }

    /**
     * Get Account
     */
    public function getAccount($token)
    {
        $provider = $this->getProvider();

        $realToken = OauthHelper::getRealToken($token);

        $response = $provider->getUserDetails($realToken);

        return $response->getIterator();
    }
}