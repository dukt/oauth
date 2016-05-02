<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2016, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Dukt\OAuth\Providers;

use Craft\Oauth_ResourceOwnerModel;
use Craft\OauthPlugin;
use Craft\IOauth_Provider;
use Craft\OauthHelper;
use Craft\Oauth_ProviderInfosModel;
use Craft\Oauth_TokenModel;
use Craft\LogLevel;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\RequestException;

/**
 * Provider is the base class for classes representing providers in terms of objects.
 *
 * @author Dukt <support@dukt.net>
 * @since 1.0
 */

abstract class BaseProvider implements IOauth_Provider {

    // Properties
    // =========================================================================

    protected $token;
    protected $params = array();
    protected $providerInfos;
    protected $provider;
    protected $httpBuildEncType = 1;

    // Public Methods
    // =========================================================================

    /**
     * OAuth Connect
     */
    public function connect($options)
    {
        switch($this->getOauthVersion())
        {
            case 2:
                return $this->connectOauth2($options);
                break;

            case 1:
                return $this->connectOauth1($options);
                break;

            default:
                throw new \Exception("Couldn't handle connect for this provider because OAuth version is unknown.");
        }
    }

    /**
     * Connect OAuth 2.0
     */
    public function connectOauth2($options)
    {
        $token = false;

        // source oauth provider
        $oauthProvider = $this->getProvider();

        // google cancel
        if(\Craft\craft()->request->getParam('error'))
        {
            throw new \Exception("An error occured: ".\Craft\craft()->request->getParam('error'));
        }

        $state = \Craft\craft()->request->getParam('state');
        $code = \Craft\craft()->request->getParam('code');
        $oauth2state = \Craft\craft()->httpSession->get('oauth2state');

        if (is_null($code))
        {
            OauthPlugin::log('OAuth 2 Connect - Step 1', LogLevel::Info);

            // $oauthProvider->setScopes($options['scope']);

            $authorizationOptions = $options['authorizationOptions'];

            if(count($options['scope']) > 0)
            {
                $authorizationOptions['scope'] = $options['scope'];
            }
//
//            if(!empty($options['authorizationOptions']['access_type']) && $options['authorizationOptions']['access_type'] == 'offline')
//            {
//                unset($options['authorizationOptions']['access_type']);
//                $oauthProvider->setAccessType('offline');
//            }

            $authorizationUrl = $oauthProvider->getAuthorizationUrl($authorizationOptions);

            $state = $oauthProvider->getState();

            \Craft\craft()->httpSession->add('oauth2state', $state);

            OauthPlugin::log('OAuth 2 Connect - Step 1 - Data'."\r\n".print_r([
                'authorizationUrl' => $authorizationUrl,
                'oauth2state' => \Craft\craft()->httpSession->get('oauth2state')
            ], true), LogLevel::Info);

            \Craft\craft()->request->redirect($authorizationUrl);
        }
        elseif (!$state || $state !== $oauth2state)
        {
            OauthPlugin::log('OAuth 2 Connect - Step 1.5'."\r\n".print_r([
                'error' => "Invalid state",
                'state' => $state,
                'oauth2state' => $oauth2state,
            ], true), LogLevel::Info, true);

            \Craft\craft()->httpSession->remove('oauth2state');

            throw new \Exception("Invalid state");

        }
        else
        {
            OauthPlugin::log('OAuth 2 Connect - Step 2', LogLevel::Info, true);

            $token = $oauthProvider->getAccessToken('authorization_code', [
                'code' => $code
            ]);

            OauthPlugin::log('OAuth 2 Connect - Step 2 - Data'."\r\n".print_r([
                'code' => $code,
                'token' => $token,
            ], true), LogLevel::Info, true);
        }

        return $token;
    }

    /**
     * Connect OAuth 1.0
     */
    public function connectOauth1($options)
    {
        $token = false;

        // source oauth provider
        $oauthProvider = $this->getProvider();

        // twitter cancel
        if(\Craft\craft()->request->getParam('denied'))
        {
            throw new \Exception("An error occured: ".\Craft\craft()->request->getParam('denied'));
        }

        $user = \Craft\craft()->request->getParam('user');
        $oauth_token = \Craft\craft()->request->getParam('oauth_token');
        $oauth_verifier = \Craft\craft()->request->getParam('oauth_verifier');
        $denied = \Craft\craft()->request->getParam('denied');

        if ($oauth_token && $oauth_verifier)
        {
            OauthPlugin::log('OAuth 1 Connect - Step 2', LogLevel::Info, true);

            $temporaryCredentials = unserialize(\Craft\craft()->httpSession->get('temporary_credentials'));

            $token = $oauthProvider->getTokenCredentials($temporaryCredentials, $oauth_token, $oauth_verifier);

            \Craft\craft()->httpSession->add('token_credentials', serialize($token));

            OauthPlugin::log('OAuth 1 Connect - Step 2 - Data'."\r\n".print_r([
                'temporaryCredentials' => $temporaryCredentials,
                'oauth_token' => $oauth_token,
                'oauth_verifier' => $oauth_verifier,
                'token' => $token,
            ], true), LogLevel::Info, true);
        }
        elseif ($denied)
        {
            OauthPlugin::log('OAuth 1 Connect - Step 1.5'."\r\n".print_r(["Client access denied by the user"], true), LogLevel::Info, true);

            throw new \Exception("Client access denied by the user");
        }
        else
        {
            OauthPlugin::log('OAuth 1 Connect - Step 1', LogLevel::Info, true);

            $temporaryCredentials = $oauthProvider->getTemporaryCredentials();

            \Craft\craft()->httpSession->add('temporary_credentials', serialize($temporaryCredentials));

            $authorizationUrl = $oauthProvider->getAuthorizationUrl($temporaryCredentials);
            \Craft\craft()->request->redirect($authorizationUrl);

            OauthPlugin::log('OAuth 1 Connect - Step 1 - Data'."\r\n".print_r([
                'temporaryCredentials' => $temporaryCredentials,
                'authorizationUrl' => $authorizationUrl,
            ], true), LogLevel::Info, true);
        }

        return $token;
    }

    /**
     * Get Icon URL
     *
     * @return string
     */
    public function getIconUrl()
    {
        return null;
    }

    /**
     * Get Scope Docs URL
     *
     * @return string
     */
    public function getScopeDocsUrl()
    {
        return null;
    }

    public function getDefaultScope()
    {
        $provider = $this->getProvider();

        if($provider && method_exists($provider, 'getScopes'))
        {
            return $this->getProvider()->getScopes();
        }
    }

    /**
     * Get Guzzle Subscriber
     */
    public function getSubscriber(Oauth_TokenModel $token)
    {
        return $this->createSubscriber($token);
    }

    public function createSubscriber(Oauth_TokenModel $token)
    {
        $infos = $this->getInfos();

        switch($this->getOauthVersion())
        {
            case 1:
                return new \Guzzle\Plugin\Oauth\OauthPlugin(array(
                    'consumer_key' => $infos->clientId,
                    'consumer_secret' => $infos->clientSecret,
                    'token' => $token->accessToken,
                    'token_secret' => $token->secret
                ));

                break;

            case 2:
                require_once(CRAFT_PLUGINS_PATH.'oauth/src/Guzzle/Plugin/Oauth2Plugin.php');

                return new \Dukt\OAuth\Guzzle\Plugin\Oauth2Plugin(array(
                    'consumer_key' => $infos->clientId,
                    'consumer_secret' => $infos->clientSecret,
                    // 'authorization_method' => $this->getAuthorizationMethod(),
                    'access_token' => $token->accessToken,
                ));

                break;
        }
    }

    /**
     * Checks if the provider is configured
     */
    public function isConfigured()
    {
        if(!empty($this->providerInfos->clientId))
        {
            return true;
        }

        return false;
    }

    /**
     * Returns the remote resource owner.
     *
     * @param $token
     *
     * @return mixed
     */
    public function getRemoteResourceOwner($token)
    {
        $provider = $this->getProvider();

        $realToken = OauthHelper::getRealToken($token);

        switch($this->getOauthVersion())
        {
            case 1:
                return $provider->getUserDetails($realToken);
                break;
            case 2:
                return $provider->getResourceOwner($realToken);
                break;
        }
    }

    /**
     * Returns the resource owner.
     *
     * @param $token
     *
     * @return Oauth_ResourceOwnerModel
     */
    public function getResourceOwner($token)
    {
        $remoteResourceOwner = $this->getRemoteResourceOwner($token);

        $resourceOwner = new Oauth_ResourceOwnerModel;
        $resourceOwner->remoteId = $remoteResourceOwner->getId();

        // email
        if(method_exists($remoteResourceOwner, 'getEmail'))
        {
            $resourceOwner->email = $remoteResourceOwner->getEmail();
        }

        // name
        if(method_exists($remoteResourceOwner, 'getName'))
        {
            $resourceOwner->name = $remoteResourceOwner->getName();
        }
        elseif(method_exists($remoteResourceOwner, 'getFirstName') && method_exists($remoteResourceOwner, 'getLastName'))
        {
            $resourceOwner->name = trim($remoteResourceOwner->getFirstName()." ".$remoteResourceOwner->getLastName());
        }
        elseif(method_exists($remoteResourceOwner, 'getFirstName'))
        {
            $resourceOwner->name = $remoteResourceOwner->getFirstName();
        }
        elseif(method_exists($remoteResourceOwner, 'getLasttName'))
        {
            $resourceOwner->name = $remoteResourceOwner->getLasttName();
        }

        return $resourceOwner;
    }

    /**
     * Alias for getResourceOwner()
     *
     * @param $token
     *
     * @return Oauth_ResourceOwnerModel
     */
    public function getAccount($token)
    {
        return $this->getResourceOwner($token);
    }

    /**
     * Fetch provider data
     *
     * @param       $url
     * @param array $headers
     *
     * @return mixed
     */
    protected function fetchProviderData($url, array $headers = [])
    {
        $client = $this->getProvider()->getHttpClient();
        $client->setBaseUrl($url);

        if ($headers)
        {
            $client->setDefaultOption('headers', $headers);
        }

        $request = $client->get()->send();
        $response = $request->getBody();

        return $response;
    }

    /**
     * Returns the redirect URI
     *
     * @return array|string
     */
    public function getRedirectUri()
    {
        return OauthHelper::getSiteActionUrl('oauth/connect');
    }

    /**
     * Returns the provider handle based on its class name.
     *
     * @return string
     */
    public function getHandle()
    {
        $class = $this->getClass();

        $handle = strtolower($class);

        return $handle;
    }

    /**
     * Get provider class
     *
     * from : Dukt\OAuth\Providers\Dribbble
     * to : Dribbble
     *
     * @return string
     */
    public function getClass()
    {
        $nsClass = get_class($this);

        $class = substr($nsClass, strrpos($nsClass, "\\") + 1);

        return $class;
    }

    /**
     * Get provider's tokens
     *
     * @return array
     */
    public function getTokens()
    {
        return \Craft\craft()->oauth->getTokensByProvider($this->getHandle());
    }

    /**
     * Get Provider
     */
    public function getProvider()
    {
        if (!isset($this->provider))
        {
            $this->provider = $this->createProvider();
        }

        return $this->provider;
    }

    /**
     * Set Infos
     */
    public function setInfos(Oauth_ProviderInfosModel $provider)
    {
        $this->providerInfos = $provider;
    }

    /**
     * Get Infos
     */
    public function getInfos()
    {
        return $this->providerInfos;
    }
}
