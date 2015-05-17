<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2015, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Dukt\OAuth\Providers;

use Dukt\OAuth\base\Provider;

class Twitter extends Provider
{
    // Properties
    // =========================================================================

    public $consoleUrl = 'https://dev.twitter.com/apps';
    public $oauthVersion = 1;

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
     * Create Twitter Provider
     *
     * @return Twitter
     */
    public function createProvider()
    {
        $config = [
            'identifier' => $this->providerInfos->clientId,
            'secret' => $this->providerInfos->clientSecret,
            'callback_uri' => $this->getRedirectUri(),
        ];

        return new \League\OAuth1\Client\Server\Twitter($config);
    }
}