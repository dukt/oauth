<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2016, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Craft;

class OauthHelper
{
    // Public Methods
    // =========================================================================

    /**
     * Get real token
     */
    public static function getRealToken(Oauth_TokenModel $token)
    {
        $provider = craft()->oauth->getProvider($token->providerHandle);

        if($provider)
        {
            switch($provider->getOauthVersion())
            {
                case 1:
                $realToken = new \League\OAuth1\Client\Credentials\TokenCredentials();
                $realToken->setIdentifier($token->accessToken);
                $realToken->setSecret($token->secret);
                break;


                case 2:
                $realToken = new \League\OAuth2\Client\Token\AccessToken([
                    'access_token' => $token->accessToken,
                    'refresh_token' => $token->refreshToken,
                    'secret' => $token->secret,
                    'expires' => $token->endOfLife,
                ]);

                break;
            }

            return $realToken;
        }
    }

    /**
     * Token to array
     */
    public static function tokenToArray(Oauth_TokenModel $token)
    {
        return $token->getAttributes();
    }

    /**
     * Array to token
     */
    public static function arrayToToken(array $array)
    {
        $token = new Oauth_TokenModel;
        $token->setAttributes($array);

        return $token;
    }

    /**
     * Real token to array
     */
    public static function realTokenToArray($token)
    {
        $class = get_class($token);

        $tokenArray = array(
            'class' => $class,

            // 'extraParams' => $token->getExtraParams(),
        );

        switch($class)
        {
            case 'League\OAuth1\Client\Credentials\TokenCredentials':
            $tokenArray['identifier'] = $token->getIdentifier();
            $tokenArray['secret'] = $token->getSecret();
            break;

            case 'League\OAuth2\Client\Token\AccessToken':
            $tokenArray['accessToken'] = $token->getToken();
            $tokenArray['refreshToken'] = $token->getRefreshToken();
            $tokenArray['endOfLife'] = $token->getExpires();
            break;
        }

        return $tokenArray;
    }

    // Modified Craft getSiteUrl to gain more control over showing script name and admin trigger
    // =========================================================================

    /**
     * Get site action URL
     *
     * @param string $path
     * @param null   $params
     * @param string $protocol The protocol to use (e.g. http, https). If empty, the protocol used for the current
     *                         request will be used.
     *
     * @return array|string
     */
    public static function getSiteActionUrl($path = '', $params = null, $protocol = '')
    {
        $path = craft()->config->get('actionTrigger').'/'.trim($path, '/');
        return static::getSiteUrl($path, $params, $protocol, true, true);
    }

    /**
     * Returns a site URL (added mustShowScriptName arg)
     *
     * @param string $path
     * @param array|string|null $params
     * @param string|null $protocol
     * @param bool|false $dynamicBaseUrl
     * @param bool|false $mustShowScriptName
     *
     * @return string
     */
    public static function getSiteUrl($path = '', $params = null, $protocol = '', $dynamicBaseUrl = false, $mustShowScriptName = false)
    {
        $path = trim($path, '/');
        return static::_getUrl($path, $params, $protocol, $dynamicBaseUrl, $mustShowScriptName);
    }

    /**
     * Returns a URL.
     *
     * @param string       $path
     * @param array|string $params
     * @param              $protocol
     * @param              $cpUrl
     * @param              $mustShowScriptName
     *
     * @return string
     */
    private static function _getUrl($path, $params, $protocol, $cpUrl, $mustShowScriptName)
    {
        // Normalize the params
        $params = static::_normalizeParams($params, $anchor);

        // Were there already any query string params in the path?
        if (($qpos = strpos($path, '?')) !== false)
        {
            $params = substr($path, $qpos+1).($params ? '&'.$params : '');
            $path = substr($path, 0, $qpos);
        }

        $showScriptName = ($mustShowScriptName || !craft()->config->omitScriptNameInUrls());

        if ($cpUrl)
        {
            // Did they set the base URL manually?
            $baseUrl = craft()->config->get('baseCpUrl');

            if ($baseUrl)
            {
                // Make sure it ends in a slash
                $baseUrl = rtrim($baseUrl, '/').'/';

                if ($protocol)
                {
                    // Make sure we're using the right protocol
                    $baseUrl = static::getUrlWithProtocol($baseUrl, $protocol);
                }

                // Should we be adding that script name in?
                if ($showScriptName)
                {
                    $baseUrl .= craft()->request->getScriptName();
                }
            }
            else
            {
                // Figure it out for ourselves, then
                $baseUrl = craft()->request->getHostInfo($protocol);

                if ($showScriptName)
                {
                    $baseUrl .= craft()->request->getScriptUrl();
                }
                else
                {
                    $baseUrl .= craft()->request->getBaseUrl();
                }
            }
        }
        else
        {
            $baseUrl = craft()->getSiteUrl($protocol);

            // Should we be adding that script name in?
            if ($showScriptName)
            {
                $baseUrl .= craft()->request->getScriptName();
            }
        }

        // Put it all together
        if (!$showScriptName || craft()->config->usePathInfo())
        {
            if ($path)
            {
                $url = rtrim($baseUrl, '/').'/'.trim($path, '/');

                if (craft()->request->isSiteRequest() && craft()->config->get('addTrailingSlashesToUrls'))
                {
                    $url .= '/';
                }
            }
            else
            {
                $url = $baseUrl;
            }
        }
        else
        {
            $url = $baseUrl;

            if ($path)
            {
                $params = craft()->urlManager->pathParam.'='.$path.($params ? '&'.$params : '');
            }
        }

        if ($params)
        {
            $url .= '?'.$params;
        }

        if ($anchor)
        {
            $url .= $anchor;
        }

        return $url;
    }

    /**
     * Normalizes query string params.
     *
     * @param string|array|null $params
     * @param string|null       &$anchor
     *
     * @return string
     */
    private static function _normalizeParams($params, &$anchor = '')
    {
        if (is_array($params))
        {
            foreach ($params as $name => $value)
            {
                if (!is_numeric($name))
                {
                    if ($name == '#')
                    {
                        $anchor = '#'.$value;
                    }
                    else if ($value !== null && $value !== '')
                    {
                        $params[] = $name.'='.$value;
                    }

                    unset($params[$name]);
                }
            }

            $params = implode('&', array_filter($params));
        }
        else
        {
            $params = trim($params, '&?');
        }

        return $params;
    }
}
