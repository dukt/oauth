<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2015, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Dukt\OAuth\base;

use Craft\OauthHelper;
use Craft\Oauth_ProviderInfosModel;
use Craft\Oauth_TokenModel;

/**
 * Provider is the base class for classes representing providers in terms of objects.
 *
 * @author Dukt <support@dukt.net>
 * @since 1.0
 */

abstract class Provider {

    // Properties
    // =========================================================================

    public $class;
    public $storage = null;
    public $token = null;
    public $providerInfos = null;
    protected $_provider;
    protected $service = null;
    protected $scopes = array();
    protected $httpBuildEncType = 1;

    // Public Methods
    // =========================================================================

    public function getUserDetails()
    {
        $token = OauthHelper::getRealToken($this->token);

        return $this->_provider->getUserDetails($token);
    }

    public function getAuthorizationUrl($options = array())
    {
        $this->_provider->state = isset($options['state']) ? $options['state'] : md5(uniqid(rand(), true));

        //options as params
        $params = $options;

        // set default
        $params['client_id'] = $this->_provider->clientId;
        $params['redirect_uri'] = $this->_provider->redirectUri;
        $params['state'] = $this->_provider->state;
        $params['scope'] = is_array($this->_provider->scopes) ? implode($this->_provider->scopeSeparator, $this->_provider->scopes) : $this->_provider->scopes;
        $params['response_type'] = isset($options['response_type']) ? $options['response_type'] : 'code';
        $params['approval_prompt'] = isset($options['approval_prompt']) ? $options['approval_prompt'] : 'auto';

        return $this->_provider->urlAuthorize().'?'.$this->httpBuildQuery($params, '', '&');
    }

    /**
     * Build HTTP the HTTP query, handling PHP version control options
     *
     * @param  array        $params
     * @param  integer      $numeric_prefix
     * @param  string       $arg_separator
     * @param  null|integer $enc_type
     *
     * @return string
     * @codeCoverageIgnoreStart
     */
    protected function httpBuildQuery($params, $numeric_prefix = 0, $arg_separator = '&', $enc_type = null)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=') && !defined('HHVM_VERSION')) {
            if ($enc_type === null) {
                $enc_type = $this->httpBuildEncType;
            }
            $url = http_build_query($params, $numeric_prefix, $arg_separator, $enc_type);
        } else {
            $url = http_build_query($params, $numeric_prefix, $arg_separator);
        }

        return $url;
    }

    public function getRedirectUri()
    {
        return OauthHelper::getSiteActionUrl('oauth/connect');
    }

    public function getHandle()
    {
        $class = $this->getClass();

        $handle = strtolower($class);

        return $handle;
    }

    public function getClass()
    {
        // from : Dukt\OAuth\Providers\Dribbble
        // to : Dribbble

        $nsClass = get_class($this);

        $class = substr($nsClass, strrpos($nsClass, "\\") + 1);

        return $class;
    }

    public function getTokens()
    {
        return \Craft\craft()->oauth->getTokensByProvider($this->getHandle());
    }

    public function initService()
    {
        $this->getProvider();
    }

    public function getProvider()
    {
        if (!isset($this->_provider))
        {
            $this->_provider = $this->createProvider();
        }

        return $this->_provider;
    }

    public function getAuthorizationMethod()
    {
        return null;
    }

    public function setInfos(Oauth_ProviderInfosModel $provider)
    {
        // set provider
        $this->providerInfos = $provider;

        // re-initialize service with new scope
        $this->initService();
    }

    public function getInfos()
    {
        return $this->providerInfos;
    }

    public function setScopes(array $scopes)
    {
        $this->_provider->scopes = $scopes;
    }

    public function getScopes()
    {
        return $this->scopes;
    }

    public function getParams()
    {
        return array();
    }

    public function setToken(Oauth_TokenModel $token)
    {
        $this->token = $token;
    }

    public function getToken()
    {
        return $this->token;
    }

    /**
     * Is Configured ?
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
     * Get Guzzle Subscriber
     */
    public function getSubscriber()
    {
        $headers = array();
        $query = array();

        $infos = $this->getInfos();
        $token = $this->token;

        switch($this->oauthVersion)
        {
            case 1:
                $oauth = new \Guzzle\Plugin\Oauth\OauthPlugin(array(
                    'consumer_key' => $infos->clientId,
                    'consumer_secret' => $infos->clientSecret,
                    'token' => $token->accessToken,
                    'token_secret' => $token->secret
                ));

                return $oauth;

                break;

            case 2:
                $config = array(
                    'consumer_key' => $infos->clientId,
                    'consumer_secret' => $infos->clientSecret,
                    'authorization_method' => $this->getAuthorizationMethod(),
                    'access_token' => $token->accessToken,
                );

                $oauth = new \Dukt\OAuth\Guzzle\Plugin\Oauth2Plugin($config);

                return $oauth;

                break;
        }
    }

    /**
     * Get Account (alias)
     *
     * @deprecated Deprecated in 1.0.
     * @return array
     */
    public function getAccount()
    {
        return $this->getUserDetails();
    }
}