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

class Vimeo extends Provider {

    public $consoleUrl = 'https://developer.vimeo.com/apps';
    public $oauthVersion = 2;

    /**
     * Get Name
     *
     * @return string
     */
    public function getName()
    {
        return 'Vimeo';
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