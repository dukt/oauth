# Adding a custom OAuth provider

OAuth plugin for Craft CMS is extensible and developers can add a custom provider
by simply creating a plugin for Craft containing the OAuth provider.

## Plugin

    <?php
    namespace Craft;

    class GithubPlugin extends BasePlugin
    {
        /**
         * Get Name
         */
        function getName()
        {
            return Craft::t('GitHub');
        }

        /**
         * Get OAuth providers
         */
        public function getOauthProviders()
        {
            require_once(CRAFT_PLUGINS_PATH.'github/providers/Github.php');

            return [
                'Dukt\Github\Social\Gateway\Github'
            ];
        }

        ...
    }


## OAuth Provider

    <?php

    namespace Dukt\OAuth\Providers;

    use Craft\UrlHelper;

    class Facebook extends BaseProvider
    {
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
         * Get Icon URL
         *
         * @return string
         */
        public function getIconUrl()
        {
            return UrlHelper::getResourceUrl('oauth/providers/facebook.svg');
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
         * Create Facebook Provider
         *
         * @return League\OAuth2\Client\Provider\Facebook
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
         * Get API Manager URL
         *
         * @return string
         */
        public function getManagerUrl()
        {
            return 'https://developers.facebook.com/apps';
        }

        /**
         * Get Scope Docs URL
         *
         * @return string
         */
        public function getScopeDocsUrl()
        {
            return 'https://developers.facebook.com/docs/facebook-login/permissions/v2.5';
        }
    }