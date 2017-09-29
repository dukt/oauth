<?php
namespace Dukt\OAuth\Providers;

use Craft\UrlHelper;

class Instagram extends BaseProvider
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the provider's name.
     *
     * @return string
     */
    public function getName()
    {
        return 'Instagram';
    }

    /**
     * Returns the provider's icon URL.
     *
     * @return string
     */
    public function getIconUrl()
    {
        return UrlHelper::getResourceUrl('oauth/providers/instagram.svg');
    }

    /**
     * Returns the OAuth version the provider uses.
     *
     * @return int
     */
    public function getOauthVersion()
    {
        return 2;
    }

    /**
     * Returns the URL of the provider's API Manager.
     *
     * @return string
     */
    public function getManagerUrl()
    {
        return 'https://www.instagram.com/developer/clients/manage/';
    }

    /**
     * Returns the URL of the provider's Scope Documentation.
     *
     * @return string
     */
    public function getScopeDocsUrl()
    {
        return 'https://www.instagram.com/developer/authorization/';
    }

    /**
     * Creates the provider.
     *
     * @return Instagram
     */
    public function createProvider()
    {
        $config = [
            'clientId' => $this->providerInfos->clientId,
            'clientSecret' => $this->providerInfos->clientSecret,
            'redirectUri' => $this->getRedirectUri(),
        ];

        return new \League\OAuth2\Client\Provider\Instagram($config);
    }
}
