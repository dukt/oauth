<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2015, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Dukt\OAuth\Providers;

use Dukt\OAuth\base\Provider;

class Facebook extends Provider
{
    // Properties
    // =========================================================================

    public $consoleUrl = 'https://developers.facebook.com/apps';
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
        return 'Facebook';
    }

    /**
     * Create Facebook Provider
     *
     * @return Facebook
     */
    public function createProvider()
    {
        $config = [
            'clientId' => $this->providerInfos->clientId,
            'clientSecret' => $this->providerInfos->clientSecret,
            'redirectUri' => $this->getRedirectUri(),
        ];

        return new \League\OAuth2\Client\Provider\Facebook($config);
    }

    /**
     * Facebook refuses to accept the redirect URL if the query string is not
     * URL encoded.
     *
     * @return string
     */
    public function getRedirectUri()
    {
        $url = parent::getRedirectUri();

        if (preg_match('/^(.+\?p=)(.+)$/', $url, $matches)) {
            $url = $matches[1] . urlencode($matches[2]);
        }

        return $url;
    }
}