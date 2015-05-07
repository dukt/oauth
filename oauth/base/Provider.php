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

    protected $token;
    protected $params = array();
    protected $providerInfos;
    protected $provider;
    protected $httpBuildEncType = 1;

    // Public Methods
    // =========================================================================

    /**
     * Get Account
     */
    public function getAccount()
    {
        $token = OauthHelper::getRealToken($this->token);

        return $this->getProvider()->getUserDetails($token);
    }

    /**
     * Get Authorization URL
     */
    public function getAuthorizationUrl($options = array())
    {
        $this->getProvider()->state = isset($options['state']) ? $options['state'] : md5(uniqid(rand(), true));

        //options as params
        $params = $options;

        // set default
        $params['client_id'] = $this->getProvider()->clientId;
        $params['redirect_uri'] = $this->getProvider()->redirectUri;
        $params['state'] = $this->getProvider()->state;
        $params['scope'] = is_array($this->getProvider()->scopes) ? implode($this->getProvider()->scopeSeparator, $this->getProvider()->scopes) : $this->getProvider()->scopes;
        $params['response_type'] = isset($options['response_type']) ? $options['response_type'] : 'code';
        $params['approval_prompt'] = isset($options['approval_prompt']) ? $options['approval_prompt'] : 'auto';

        return $this->getProvider()->urlAuthorize().'?'.$this->httpBuildQuery($params, '', '&');
    }

    /**
     * Get Redirect URI
     */
    public function getRedirectUri()
    {
        return OauthHelper::getSiteActionUrl('oauth/connect');
    }

    /**
     * Get Handle
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
     */
    public function getClass()
    {
        $nsClass = get_class($this);

        $class = substr($nsClass, strrpos($nsClass, "\\") + 1);

        return $class;
    }

    /**
     * Get Tokens
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
     * Get Authorization Method
     */
    public function getAuthorizationMethod()
    {
        return null;
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

    /**
     * Set Params
     */
    public function setParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * Get Params
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set Scopes
     */
    public function setScopes(array $scopes)
    {
        if(isset($this->getProvider()->scopes))
        {
            $this->getProvider()->scopes = $scopes;
        }
    }

    /**
     * Get Scopes
     */
    public function getScopes()
    {
        if(isset($this->getProvider()->scopes))
        {
            return $this->getProvider()->scopes;
        }
    }

    /**
     * Set Token
     */
    public function setToken(Oauth_TokenModel $token)
    {
        $this->token = $token;
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
                return new \Guzzle\Plugin\Oauth\OauthPlugin(array(
                    'consumer_key' => $infos->clientId,
                    'consumer_secret' => $infos->clientSecret,
                    'token' => $token->accessToken,
                    'token_secret' => $token->secret
                ));

                break;

            case 2:
                return new \Dukt\OAuth\Guzzle\Plugin\Oauth2Plugin(array(
                    'consumer_key' => $infos->clientId,
                    'consumer_secret' => $infos->clientSecret,
                    'authorization_method' => $this->getAuthorizationMethod(),
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

    // Protected Methods
    // =========================================================================

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
}