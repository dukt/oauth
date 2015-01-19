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

namespace Craft;

require_once(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');
require_once(CRAFT_PLUGINS_PATH.'oauth/providers/BaseOAuthProviderSource.php');

use ReflectionClass;
use Guzzle\Http\Client;

class OauthService extends BaseApplicationComponent
{
    private $_configuredProviders = array();
    private $_allProviders = array();
    private $_providersLoaded = false;

    public function tokenToArray($token)
    {
        $class = get_class($token);

        $tokenArray = array(
            'class' => $class,
            'accessToken' => $token->getAccessToken(),
            'endOfLife' => $token->getEndOfLife(),
            'extraParams' => $token->getExtraParams(),
        );

        switch($class)
        {
            case 'OAuth\OAuth1\Token\StdOAuth1Token':
            $tokenArray['requestToken'] = $token->getRequestToken();
            $tokenArray['requestTokenSecret'] = $token->getRequestTokenSecret();
            $tokenArray['accessTokenSecret'] = $token->getAccessTokenSecret();
            break;

            case 'OAuth\OAuth2\Token\StdOAuth2Token':
            $tokenArray['refreshToken'] = $token->getRefreshToken();
            break;
        }

        return $tokenArray;
    }

    public function arrayToToken(array $array)
    {
        $token = new $array['class'];

        $token->setAccessToken($array['accessToken']);
        $token->setEndOfLife($array['endOfLife']);
        $token->setExtraParams($array['extraParams']);

        switch($array['class'])
        {
            case 'OAuth\OAuth1\Token\StdOAuth1Token':
            $token->setRequestToken($array['requestToken']);
            $token->setRequestTokenSecret($array['requestTokenSecret']);
            $token->setAccessTokenSecret($array['accessTokenSecret']);
            break;

            case 'OAuth\OAuth2\Token\StdOAuth2Token':
            $token->setRefreshToken($array['refreshToken']);
            break;
        }

        return $token;
    }

    public function getTokensByProvider($providerHandle)
    {
        $conditions = 'providerHandle=:providerHandle';
        $params = array(':providerHandle' => $providerHandle);
        $records = Oauth_TokenRecord::model()->findAll($conditions, $params);
        return Oauth_TokenModel::populateModels($records);
    }

    public function getToken($encodedToken)
    {
        $conditions = 'encodedToken=:encodedToken';
        $params = array(':encodedToken' => $encodedToken);
        $record = Oauth_TokenRecord::model()->find($conditions, $params);

        if($record)
        {
            return Oauth_TokenModel::populateModel($record);
        }
    }

    /**
     * Delete token ID
     */
    public function deleteToken(Oauth_TokenModel $token)
    {
        if (!$token->id)
        {
            return false;
        }

        $record = Oauth_TokenRecord::model()->findById($token->id);

        if($record)
        {
            return $record->delete();
        }

        return false;
    }

    public function deleteTokensByPlugin($pluginHandle)
    {
        $conditions = 'pluginHandle=:pluginHandle';
        $params = array(':pluginHandle' => $pluginHandle);
        return Oauth_TokenRecord::model()->deleteAll($conditions, $params);
    }

    /**
     * Get token by ID
     */
    public function getTokenById($id)
    {

        if ($id)
        {
            $record = Oauth_TokenRecord::model()->findById($id);

            if ($record)
            {
                $token = Oauth_TokenModel::populateModel($record);

                // will refresh token if needed

                try {
                    if($this->refreshToken($token))
                    {
                        // save refreshed token
                        $this->saveToken($token);
                    }
                }
                catch(\Exception $e)
                {
                    // todo: log
                    // throw $e;

                    // return null;
                }

                return $token;
            }
        }
    }

    /**
     * Save token
     */
    public function saveToken(Oauth_TokenModel &$model)
    {
        // is new ?
        $isNewToken = !$model->id;

        // populate record
        $record = $this->getTokenRecordById($model->id);
        $record->providerHandle = strtolower($model->providerHandle);
        $record->pluginHandle = strtolower($model->pluginHandle);
        $record->accessToken = $model->accessToken;
        $record->secret = $model->secret;
        $record->endOfLife = $model->endOfLife;
        $record->refreshToken = $model->refreshToken;

        // save record
        if($record->save(false))
        {
            // populate id
            if($isNewToken)
            {
                $model->id = $record->id;
            }

            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Get token record by ID
     */
    private function getTokenRecordById($id = null)
    {
        if ($id)
        {
            $record = Oauth_TokenRecord::model()->findById($id);

            if (!$record)
            {
                throw new Exception(Craft::t('No oauth token exists with the ID “{id}”', array('id' => $id)));
            }
        }
        else
        {
            $record = new Oauth_TokenRecord();
        }

        return $record;
    }

    public function refreshToken(Oauth_TokenModel $model)
    {
        if(is_object($model))
        {
            $time = time();

            // $time = time() + 3590; // google ttl
            // $time = time() + 50400005089; // facebook ttl

            // has token expired ?

            if($time > $model->endOfLife)
            {
                $realToken = $this->getRealToken($model);
                $provider = craft()->oauth->getProvider($model->providerHandle);
                $infos = $provider->getInfos();
                $newToken = $provider->refreshAccessToken($realToken);


                // make new token current

                $model->accessToken = $newToken->getAccessToken();

                if(method_exists($newToken, 'getAccessTokenSecret'))
                {
                    $model->secret = $newToken->getAccessTokenSecret();
                }

                $model->endOfLife = $newToken->getEndOfLife();
                $model->refreshToken = $newToken->getRefreshToken();
            }
        }

        return false;
    }

    public function getRealToken(Oauth_TokenModel $token)
    {
        $provider = $this->getProvider($token->providerHandle);

        switch($provider->oauthVersion)
        {
            case 1:
            $realToken = new \OAuth\OAuth1\Token\StdOAuth1Token();
            $realToken->setAccessTokenSecret($token->secret);
            break;


            case 2:
            $realToken = new \OAuth\OAuth2\Token\StdOAuth2Token();
            break;
        }

        $realToken->setAccessToken($token->accessToken);
        $realToken->setEndOfLife($token->endOfLife);
        $realToken->setRefreshToken($token->refreshToken);

        return $realToken;
    }

    public function connect($variables)
    {
        if(!craft()->httpSession->get('oauth.response'))
        {
            craft()->oauth->sessionClean();

            if(!empty($variables['referer']))
            {
                $referer = $variables['referer'];
            }
            else
            {
                $referer = craft()->request->getUrl();
            }

            craft()->httpSession->add('oauth.referer', $referer);


            // redirect

            if(!empty($variables['redirect']))
            {
                $redirect = $variables['redirect'];
            }
            else
            {
                $redirect = craft()->request->getUrlReferrer();
            }

            craft()->httpSession->add('oauth.redirect', $redirect);


            // error redirect

            if(!empty($variables['errorRedirect']))
            {
                $errorRedirect = $variables['errorRedirect'];
            }
            else
            {
                $errorRedirect = craft()->request->getUrlReferrer();
            }

            craft()->httpSession->add('oauth.errorRedirect', $errorRedirect);


            // scopes

            if(!empty($variables['scopes']))
            {
                $scopes = $variables['scopes'];
            }
            else
            {
                $scopes = array();
            }

            craft()->httpSession->add('oauth.scopes', $scopes);


            // params

            if(!empty($variables['params']))
            {
                $params = $variables['params'];
            }
            else
            {
                $params = array();
            }

            craft()->httpSession->add('oauth.params', $params);

            // redirect
            craft()->request->redirect(UrlHelper::getActionUrl('oauth/connect/', array(
                'provider' => $variables['provider']
            )));
        }
        else
        {
            $response = craft()->httpSession->get('oauth.response');

            if(!empty($response['token']))
            {
                $token = new Oauth_TokenModel;
                $token->accessToken = $response['token']['accessToken'];

                if(!empty($response['token']['accessTokenSecret']))
                {
                    $token->secret = $response['token']['accessTokenSecret'];
                }

                $token->endOfLife = $response['token']['endOfLife'];

                if(!empty($response['token']['refreshToken']))
                {
                    $token->refreshToken = $response['token']['refreshToken'];
                }

                $response['token'] = $token;
            }

            craft()->oauth->sessionClean();

            return $response;
        }
    }

    public function callbackUrl($handle)
    {
        $params = array('provider' => $handle);
        $params = array();

        return $this->getSiteActionUrl('oauth/connect', $params);
    }

    public function getProvider($handle,  $configuredOnly = true, $fromRecord = false)
    {
        $this->_loadProviders($fromRecord);

        $lcHandle = strtolower($handle);

        if($configuredOnly)
        {
            if(isset($this->_configuredProviders[$lcHandle]))
            {
                return $this->_configuredProviders[$lcHandle];
            }
        }
        else
        {
            if(isset($this->_allProviders[$lcHandle]))
            {
                return $this->_allProviders[$lcHandle];
            }
        }

        return null;
    }

    public function getProviders($configuredOnly = true)
    {
        $this->_loadProviders();

        if($configuredOnly)
        {
            return $this->_configuredProviders;
        }
        else
        {
            return $this->_allProviders;
        }
    }

    public function providerSave(Oauth_ProviderInfosModel $model)
    {
        // save record

        $record = $this->_getProviderRecordById($model->id);
        $record->class = $model->class;
        $record->clientId = $model->clientId;
        $record->clientSecret = $model->clientSecret;

        return $record->save(false);
    }

    public function sessionClean()
    {
        craft()->httpSession->remove('oauth.handle');
        craft()->httpSession->remove('oauth.referer');
        craft()->httpSession->remove('oauth.params');
        craft()->httpSession->remove('oauth.redirect');
        craft()->httpSession->remove('oauth.response');
        craft()->httpSession->remove('oauth.scopes');
    }

    private function _getProviderRecordByHandle($handle)
    {
        $providerRecord = Oauth_ProviderInfosRecord::model()->find(

            // conditions
            'class=:provider',

            // params
            array(
                ':provider' => $handle
            )
        );

        if($providerRecord)
        {
            return $providerRecord;
        }

        return null;
    }

    private function _getProviderRecordById($providerId = null)
    {
        if ($providerId)
        {
            $providerRecord = Oauth_ProviderInfosRecord::model()->findById($providerId);

            if (!$providerRecord)
            {
                throw new Exception(Craft::t('No oauth provider exists with the ID “{id}”', array('id' => $providerId)));
            }
        }
        else
        {
            $providerRecord = new Oauth_ProviderInfosRecord();
        }

        return $providerRecord;
    }

    public function getProviderInfos($handle)
    {
        $record = $this->_getProviderRecordByHandle($handle);

        if($record)
        {
            return Oauth_ProviderInfosModel::populateModel($record);
        }
    }

    /**
     * Loads the configured providers.
     */
    private function _loadProviders($fromRecord = false)
    {
        if($this->_providersLoaded)
        {
            return;
        }

        $providerSources = $this->getProviderSources();

        foreach($providerSources as $providerSource)
        {
            // handle
            $handle = $providerSource->getHandle();

            // get provider record
            $record = $this->_getProviderRecordByHandle($providerSource->getHandle());

            // create provider (from record if any)
            $providerInfos = Oauth_ProviderInfosModel::populateModel($record);


            // override provider infos from config

            $oauthConfig = craft()->config->get('oauth');

            if($oauthConfig && !$fromRecord)
            {
                if(!empty($oauthConfig[$providerSource->getHandle()]['clientId']))
                {
                    $providerInfos->clientId = $oauthConfig[$providerSource->getHandle()]['clientId'];
                }

                if(!empty($oauthConfig[$providerSource->getHandle()]['clientSecret']))
                {
                    $providerInfos->clientSecret = $oauthConfig[$providerSource->getHandle()]['clientSecret'];
                }
            }

            $providerSource->setInfos($providerInfos);

            if($providerSource->isConfigured())
            {
                $this->_configuredProviders[$handle] = $providerSource;
            }

            // add to _allProviders array
            $this->_allProviders[$handle] = $providerSource;
        }

        // providers are now loaded
        $this->_providersLoaded = true;
    }

    public function getProviderSources()
    {
        $providerSources = array();

        $providersPath = CRAFT_PLUGINS_PATH.'oauth/providers/';
        $providersFolderContents = IOHelper::getFolderContents($providersPath, false);

        if($providersFolderContents)
        {
            foreach($providersFolderContents as $path)
            {
                $path = IOHelper::normalizePathSeparators($path);
                $fileName = IOHelper::getFileName($path, false);

                if($fileName == 'BaseOAuthProviderSource') continue;

                // Chop off the "OAuthProviderSource" suffix
                $handle = substr($fileName, 0, strlen($fileName) - 19);

                $providerSources[] = $this->getProviderSource($handle);
            }
        }

        return $providerSources;
    }

    public function getProviderSource($providerClass)
    {
        // Get the full class name

        $class = $providerClass.'OAuthProviderSource';

        $nsClass = 'OAuthProviderSources\\'.$class;


        // Skip the autoloader

        if (!class_exists($nsClass, false))
        {
            $path = CRAFT_PLUGINS_PATH.'oauth/providers/'.$class.'.php';

            if (($path = IOHelper::fileExists($path, false)) !== false)
            {
                require_once $path;
            }
            else
            {
                return null;
            }
        }

        if (!class_exists($nsClass, false))
        {
            return null;
        }

        $providerSource = new $nsClass;

        if (!$providerSource instanceof \OAuthProviderSources\BaseOAuthProviderSource)
        {
            die("this provider doesn't implement BaseOAuthProviderSource abstract class");
        }

        return $providerSource;
    }













    public function getTestToken($providerHandle)
    {
        $token = craft()->httpSession->get('oauth.test.'.$providerHandle);
    }

    public function saveTestToken($providerHandle, $token)
    {
        craft()->httpSession->add('oauth.test.'.$providerHandle, $token);
    }





























    // getSiteActionUrl

    public function getSiteActionUrl($path = '', $params = null, $protocol = '')
    {
        $path = craft()->config->get('actionTrigger').'/'.trim($path, '/');
        return $this->getSiteUrl($path, $params, $protocol, true, true);
    }


    // getSiteUrl with mustShowScriptName arg

    public function getSiteUrl($path = '', $params = null, $protocol = '', $dynamicBaseUrl = false, $mustShowScriptName = false)
    {
        $path = trim($path, '/');
        return static::_getUrl($path, $params, $protocol, $dynamicBaseUrl, $mustShowScriptName);
    }


    // copy of _getUrl

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