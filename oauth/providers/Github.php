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

use Guzzle\Http\Client;
use Dukt\OAuth\base\Provider;

class Github extends Provider {

    public $consoleUrl = 'https://github.com/settings/applications/';
    public $oauthVersion = 2;

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

        return new \League\OAuth2\Client\Provider\Github($config);
    }
}