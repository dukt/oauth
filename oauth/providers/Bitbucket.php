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

namespace Dukt\OAuth\Providers;

use Dukt\OAuth\base\Provider;

class Bitbucket extends Provider {

    public $consoleUrl = 'https://bitbucket.org/account/';
    public $oauthVersion = 1;

    /**
     * Get Name
     *
     * @return string
     */
    public function getName()
    {
        return 'BitBucket';
    }

    /**
     * Create BitBucket Provider
     *
     * @return BitBucket
     */
    public function createProvider()
    {
        $config = [
            'identifier' => $this->providerInfos->clientId,
            'secret' => $this->providerInfos->clientSecret,
            'callback_uri' => $this->getRedirectUri(),
        ];

        return new \League\OAuth1\Client\Server\Bitbucket($config);
    }
}