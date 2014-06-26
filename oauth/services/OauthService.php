<?php

/**
 * Craft OAuth by Dukt
 *
 * @package   Craft OAuth
 * @author    Benjamin David
 * @copyright Copyright (c) 2014, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 * @link      https://dukt.net/craft/oauth/
 */

namespace Craft;

require_once(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');
require_once(CRAFT_PLUGINS_PATH.'oauth/providers/BaseOAuthProviderSource.php');

use ReflectionClass;

class OauthService extends BaseApplicationComponent
{
    private $_configuredProviders = array();
    private $_allProviders = array();
    private $_providersLoaded = false;

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
        return Oauth_TokenModel::populateModel($record);
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
        $record->encodedToken = $model->encodedToken;

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

    public function getTestToken($providerHandle)
    {
        $token = craft()->httpSession->get('oauth.test.'.$providerHandle);

        if($token)
        {
            return craft()->oauth->decodeToken($token);
        }
    }

    public function saveTestToken($providerHandle, $token)
    {
        $token = craft()->oauth->encodeToken($token);

        craft()->httpSession->add('oauth.test.'.$providerHandle, $token);
    }

    public function encodeToken($token)
    {
        if($token)
        {
            return base64_encode(serialize($token));
        }
    }

    public function decodeToken($token)
    {
        if($token)
        {
            return unserialize(base64_decode($token));
        }
    }

    public function refreshToken(Oauth_TokenModel &$model)
    {
        $token = $model->getToken();

        if(is_object($token))
        {
            $provider = craft()->oauth->getProvider($model->providerHandle);
            $provider->source->setToken($token);

            $token = $provider->source->retrieveAccessToken();

            if(time() > 0)
            {
                // refresh token
                if(method_exists($provider->source->service, 'refreshAccessToken'))
                {
                    if($token->getRefreshToken())
                    {
                        // generate new token
                        $newToken = $provider->source->service->refreshAccessToken($token);

                        // keep our refresh token as it always remains valid
                        $newToken->setRefreshToken($token->getRefreshToken());

                        // make new token current
                        $model->encodedToken = $this->encodeToken($newToken);

                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function connect($variables)
    {
        if(!craft()->httpSession->get('oauth.response'))
        {
            craft()->oauth->sessionClean();

            craft()->httpSession->add('oauth.referer', craft()->request->getUrl());


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

    public function getProvider($handle,  $configuredOnly = true)
    {
        $this->_loadProviders();

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

    public function providerSave(Oauth_ProviderModel $model)
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
        $providerRecord = Oauth_ProviderRecord::model()->find(

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
            $providerRecord = Oauth_ProviderRecord::model()->findById($providerId);

            if (!$providerRecord)
            {
                throw new Exception(Craft::t('No oauth provider exists with the ID “{id}”', array('id' => $providerId)));
            }
        }
        else
        {
            $providerRecord = new Oauth_ProviderRecord();
        }

        return $providerRecord;
    }

    private function _getProviderRecords()
    {
        $records = Oauth_ProviderRecord::model()->findAll();

        return $records;
    }

    /**
     * Loads the configured providers.
     */
    private function _loadProviders()
    {
        if($this->_providersLoaded)
        {
            return;
        }


        // providers

        foreach($this->getProviderSources() as $providerSource)
        {
            $lcHandle = strtolower($providerSource->getHandle());

            $record = $this->_getProviderRecordByHandle($providerSource->getHandle());

            $provider = Oauth_ProviderModel::populateModel($record);
            $provider->class = $providerSource->getHandle();


            // source

            if($record && !empty($provider->clientId))
            {
                // client id and secret

                $clientId = false;
                $clientSecret = false;

                // ...from config

                $oauthConfig = craft()->config->get('oauth');

                if($oauthConfig)
                {

                    if(!empty($oauthConfig[$providerSource->getHandle()]['clientId']))
                    {
                        $clientId = $oauthConfig[$providerSource->getHandle()]['clientId'];
                    }

                    if(!empty($oauthConfig[$providerSource->getHandle()]['clientSecret']))
                    {
                        $clientSecret = $oauthConfig[$providerSource->getHandle()]['clientSecret'];
                    }
                }

                // ...from provider

                if(!$clientId)
                {
                    $clientId = $provider->clientId;
                }

                if(!$clientSecret)
                {
                    $clientSecret = $provider->clientSecret;
                }

                // source
                $providerSource->initProviderSource($clientId, $clientSecret);
                $provider->setSource($providerSource);
                $this->_configuredProviders[$lcHandle] = $provider;
            }
            else
            {
                $provider->setSource($providerSource);
            }

            $this->_allProviders[$lcHandle] = $provider;
        }

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