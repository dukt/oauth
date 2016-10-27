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
                    return $provider->createAccessToken([
                        'identifier' => $token->accessToken,
                        'secret' => $token->secret,
                    ]);

                case 2:
                    return $provider->createAccessToken([
                        'access_token' => $token->accessToken,
                        'refresh_token' => $token->refreshToken,
                        'secret' => $token->secret,
                        'expires' => $token->endOfLife,
                    ]);
            }
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

        if(get_class($token) === 'League\OAuth1\Client\Credentials\TokenCredentials' || is_subclass_of($token, '\League\OAuth1\Client\Credentials\TokenCredentials'))
        {
            // OAuth 1.0
            $tokenArray['identifier'] = $token->getIdentifier();
            $tokenArray['secret'] = $token->getSecret();
        }
        elseif(get_class($token) === 'League\OAuth2\Client\Token\AccessToken' || is_subclass_of($token, '\League\OAuth2\Client\Token\AccessToken'))
        {
            // OAuth 2.0
            $tokenArray['accessToken'] = $token->getToken();
            $tokenArray['refreshToken'] = $token->getRefreshToken();
            $tokenArray['endOfLife'] = $token->getExpires();
        }

        return $tokenArray;
    }
}
