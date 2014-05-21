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

    public function callbackUrl($handle)
    {
        $params = array('provider' => $handle);

        return $this->getSiteActionUrl('oauth/public/connect', $params);
    }

    public function getSiteActionUrl($path = '', $params = null, $protocol = '')
    {
        $path = craft()->config->get('actionTrigger').'/'.trim($path, '/');
        return $this->getSiteUrl($path, $params, $protocol, true, true);
    }

    public function connect($handle, $scope = null, $namespace = null)
    {
        $params = array('provider' => $handle);

        if($scope)
        {
            $params['scope'] = base64_encode(serialize($scope));
        }

        if($namespace)
        {
            $params['namespace'] = $namespace;
        }

        return UrlHelper::getSiteUrl(craft()->config->get('actionTrigger').'/oauth/connect', $params);
    }

    public function disconnect($handle, $namespace = null)
    {
        $params = array(
            'provider' => $handle
            );

        if($namespace)
        {
            $params['namespace'] = $namespace;
        }

        return UrlHelper::getSiteUrl(craft()->config->get('actionTrigger').'/oauth/disconnect', $params);
    }

    public function getAccount($handle, $namespace = null)
    {
        // provider
        $provider = craft()->oauth->getProvider($handle);


        // get token

        if($namespace)
        {
            $tokenRecord = $this->_getTokenRecordByNamespace($handle, $namespace);
        }
        else
        {
            $tokenRecord = $this->_getTokenRecordByCurrentUser($handle);
        }

        if(!$tokenRecord)
        {
            return null;
        }

        $token = unserialize(base64_decode($tokenRecord->token));

        if(!$token)
        {
            return null; // no token defined
        }

        $provider->setToken($token);

        try
        {
            $account = $provider->getAccount();

            if($account) {
                return $account;
            }
        }
        catch(\Exception $e)
        {
            // TODO: log info
            //die($e->getMessage());
        }

        return null;
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

    public function getToken($handle, $namespace = null, $userId = null)
    {
        try
        {
            $record = $this->_getTokenRecord($handle, $namespace, $userId);

            if($record)
            {
                $model = Oauth_TokenModel::populateModel($record);


                // refresh ?

                $realToken = $model->getDecodedToken();

                if (isset($realToken->expires))
                {
                    $remaining = $realToken->expires - time();

                    if ($remaining < 240)
                    {
                        $provider = craft()->oauth->getProvider($handle);
                        $provider->setToken($realToken);
                        // var_dump($provider);
                        // return null;
                        $provider->refreshToken();


                        $record = $this->_getTokenRecord($handle, $namespace, $userId);

                        if($record)
                        {
                            $model = Oauth_TokenModel::populateModel($record);
                        }
                    }

                }

                return $model;
            }
        }
        catch(\Exception $e)
        {
            Craft::log("Couldn't get token: ".$e->getMessage(), LogLevel::Error);
        }

        return null;
    }

    public function getTokenEncoded($encodedToken)
    {
        $criteriaConditions = '';
        $criteriaParams = array();

        $criteriaConditions = '
            token=:token
            ';

        $criteriaParams = array(
            ':token' => $encodedToken
            );

        $record = Oauth_TokenRecord::model()->find($criteriaConditions, $criteriaParams);

        $model = Oauth_TokenModel::populateModel($record);

        return $model;
    }

    public function getTokenFromUserMapping($handle, $userMapping = null)
    {
        if(!craft()->userSession->user)
        {
            return null;
        }

        $conditions = 'userMapping=:userMapping';
        $params = array(':userMapping' => $userMapping);

        $conditions .= ' AND provider=:provider';
        $params[':provider'] = $handle;

        $record = Oauth_TokenRecord::model()->find($conditions, $params);

        if(!$record)
        {
            return null;
        }

        return Oauth_TokenModel::populateModel($record);
    }

    public function getSystemToken($handle, $namespace)
    {
        $token = $this->getToken($handle, $namespace);

        if(!$token)
        {
            return null;
        }

        if(!$token->getRealToken())
        {
            return null;
        }

        return $token;
    }

    public function getSystemTokens()
    {
        $conditions = 'namespace is not null';

        $params = array();

        $records = Oauth_TokenRecord::model()->findAll($conditions, $params);

        return Oauth_TokenModel::populateModels($records);
    }

    public function getUserToken($handle, $userId = null)
    {
        $record = $this->_getTokenRecord($handle, null, $userId);

        if($record)
        {
            return Oauth_TokenModel::populateModel($record);
        }

        return null;
    }

    public function getUserTokens($userId = null)
    {
        if($userId) {
            $criteriaConditions = 'userId=:userId';
            $criteriaParams = array(':userId' => $userId);
        }
        else
        {
            $criteriaConditions = 'userId is not null';
            $criteriaParams = array();
        }

        return Oauth_TokenRecord::model()->findAll($criteriaConditions, $criteriaParams);;
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

    public function tokenDeleteById($id)
    {
        $record = Oauth_TokenRecord::model()->findByPk($id);

        if($record)
        {
            return $record->delete();
        }

        return false;
    }

    public function tokenDeleteByNamespace($handle, $namespace)
    {
        $record = $this->_getTokenRecordByNamespace($handle, $namespace);

        if($record)
        {
            return $record->delete();
        }

        return false;
    }

    public function tokenSave(Oauth_TokenModel $model)
    {


        // save record

        $record = $this->_getTokenRecordById($model->id);


        $record->userId = $model->userId;
        $record->provider = $model->provider;
        $record->namespace = $model->namespace;
        $record->userMapping = $model->userMapping;
        $record->token = $model->token;
        $record->scope = $model->scope;

        return $record->save(false);
    }

    public function sessionAdd($k, $v = null)
    {
        $returnValue = craft()->httpSession->get($k);

        if(!$returnValue && $v)
        {
            $returnValue = $v;

            craft()->httpSession->add($k, $v);
        }

        return $returnValue;
    }

    public function sessionClean()
    {
        craft()->httpSession->remove('oauth.userMode');
        craft()->httpSession->remove('oauth.referer');
        craft()->httpSession->remove('oauth.scope');
        craft()->httpSession->remove('oauth.namespace');
        craft()->httpSession->remove('oauth.provider');
        craft()->httpSession->remove('oauth.providerClass');
        craft()->httpSession->remove('oauth.token');
        craft()->httpSession->remove('oauth.social');
        craft()->httpSession->remove('oauth.socialCallback');
        craft()->httpSession->remove('oauth.socialReferer');
        craft()->httpSession->remove('oauth.socialRedirect');
    }

    public function scopeIsEnough($scope1, $scope2)
    {
        $scopeEnough = false;

        if(is_array($scope1) && is_array($scope2))
        {
            $scopeEnough = true;

            foreach($scope1 as $s1)
            {
                $scopeFound = false;

                foreach($scope2 as $s2)
                {
                    if($s2 == $s1)
                    {
                        $scopeFound = true;
                    }
                }

                if(!$scopeFound)
                {
                    $scopeEnough = false;
                    break;
                }
            }
        }

        return $scopeEnough;
    }

    public function scopeMix($scope1, $scope2)
    {
        $scope = array();

        if(is_array($scope1))
        {
            foreach($scope1 as $s1)
            {
                array_push($scope, $s1);
            }
        }

        if(is_array($scope2))
        {
            foreach($scope2 as $s1)
            {

                $scopeFound = false;

                foreach($scope as $s2)
                {
                    if($s2 == $s1)
                    {
                        $scopeFound = true;
                    }
                }

                if(!$scopeFound)
                {
                    array_push($scope, $s1);
                }
            }
        }

        if(!empty($scope))
        {
            return $scope;
        }

        return null;
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


    private function _getTokenRecord($handle, $namespace = null, $userId = null)
    {
        if($namespace)
        {
            $tokenRecord = $this->_getTokenRecordByNamespace($handle, $namespace);
        }
        elseif($userId)
        {
            $tokenRecord = $this->_getTokenRecordByUser($handle, $userId);
        }
        else
        {
            $tokenRecord = $this->_getTokenRecordByCurrentUser($handle);
        }

        if(!$tokenRecord)
        {
            return null;
        }

        return $tokenRecord;
    }

    private function _getTokenRecordById($tokenId = null)
    {
        if ($tokenId)
        {
            $tokenRecord = Oauth_TokenRecord::model()->findById($tokenId);

            if (!$tokenRecord)
            {
                throw new Exception(Craft::t('No oauth token exists with the ID “{id}”', array('id' => $tokenId)));
            }
        }
        else
        {
            $tokenRecord = new Oauth_TokenRecord();
        }

        return $tokenRecord;
    }

    private function _getTokenRecordByUser($handle, $userId)
    {
        if(!craft()->userSession->user)
        {
            return null;
        }

        $conditions = 'provider=:provider';
        $params = array(':provider' => $handle);

        $conditions .= ' AND userId=:userId';
        $params[':userId'] = $userId;

        $tokenRecord = Oauth_TokenRecord::model()->find($conditions, $params);

        if($tokenRecord)
        {
            return $tokenRecord;
        }

        return null;
    }

    private function _getTokenRecordByCurrentUser($handle)
    {
        if(!craft()->userSession->user)
        {
            return null;
        }

        $conditions = 'provider=:provider';
        $params = array(':provider' => $handle);

        $conditions .= ' AND userId=:userId';
        $params[':userId'] = craft()->userSession->user->id;

        $tokenRecord = Oauth_TokenRecord::model()->find($conditions, $params);

        if($tokenRecord)
        {
            return $tokenRecord;
        }

        return null;
    }

    private function _getTokenRecordByNamespace($handle, $namespace)
    {
        $conditions = 'provider=:provider AND namespace=:namespace';
        $params = array(
                ':provider' => $handle,
                ':namespace' => $namespace,
            );

        $tokenRecord = Oauth_TokenRecord::model()->find($conditions, $params);

        return $tokenRecord;
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

        // providerSources

        $providerSources = array();

        $providersPath = CRAFT_PLUGINS_PATH.'oauth/providers/';
        $providersFolderContents = IOHelper::getFolderContents($providersPath, false);

        if($providersFolderContents) {

            foreach($providersFolderContents as $path) {
                $path = IOHelper::normalizePathSeparators($path);
                $fileName = IOHelper::getFileName($path, false);

                if($fileName == 'BaseOAuthProviderSource') continue;

                // Chop off the "OAuthProviderSource" suffix

                $handle = substr($fileName, 0, strlen($fileName) - 19);

                $providerSource = $this->getProviderSource($handle);

                array_push($providerSources, $providerSource);

            }
        }

        // providers

        foreach($providerSources as $providerSource)
        {
            $lcHandle = strtolower($providerSource->getHandle());

            $record = $this->_getProviderRecordByHandle($providerSource->getHandle());

            $provider = Oauth_ProviderModel::populateModel($record);
            $provider->class = $providerSource->getHandle();

            if($record && !empty($provider->clientId))
            {
                $providerSource->setClient($provider->clientId, $provider->clientSecret);
                $provider->providerSource = $providerSource;
                $this->_configuredProviders[$lcHandle] = $provider;
            }
            else
            {
                $provider->providerSource = $providerSource;
            }

            $this->_allProviders[$lcHandle] = $provider;
        }

        $this->_providersLoaded = true;
    }


    /* Craft Helpers*/

    // improved UrlHelper::_getUrl
    public function getSiteUrl($path = '', $params = null, $protocol = '', $dynamicBaseUrl = false, $mustShowScriptName = false)
    {
        $path = trim($path, '/');
        return static::_getUrl($path, $params, $protocol, $dynamicBaseUrl, $mustShowScriptName);
    }

    // just a copy of UrlHelper::_getUrl
    private function _getUrl($path, $params, $protocol, $dynamicBaseUrl, $mustShowScriptName)
    {
        $anchor = '';

        // Normalize the params
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

        // Were there already any query string params in the path?
        if (($qpos = strpos($path, '?')) !== false)
        {
            $params = substr($path, $qpos+1).($params ? '&'.$params : '');
            $path = substr($path, 0, $qpos);
        }

        $showScriptName = ($mustShowScriptName || !craft()->config->omitScriptNameInUrls());

        if ($dynamicBaseUrl)
        {
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
}