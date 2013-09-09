<?php

namespace Craft;

require_once(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');
require_once(CRAFT_PLUGINS_PATH.'oauth/providers/BaseOAuthProviderSource.php');

use ReflectionClass;
use Symfony\Component\Finder\Finder;

class OauthService extends BaseApplicationComponent
{
    // --------------------------------------------------------------------

    private $_configuredProviders = array();
    private $_allProviders = array();
    private $_providersLoaded = false;

    // --------------------------------------------------------------------

    public function callbackUrl($handle)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $params = array('provider' => $handle);

        $url = UrlHelper::getSiteUrl(craft()->config->get('actionTrigger').'/oauth/public/connect', $params);

        Craft::log(__METHOD__." : Authenticate : ".$url, LogLevel::Info, true);

        return $url;
    }

    // --------------------------------------------------------------------

    public function connect($handle, $scope = null, $namespace = null)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $params = array('provider' => $handle);

        if($scope) {
            $params['scope'] = base64_encode(serialize($scope));
        }

        if($namespace) {
            $params['namespace'] = $namespace;
        }

        $url = UrlHelper::getSiteUrl(craft()->config->get('actionTrigger').'/oauth/connect', $params);

        Craft::log(__METHOD__." : Authenticate : ".$url, LogLevel::Info, true);

        return $url;
    }

    // --------------------------------------------------------------------

    public function disconnect($handle, $namespace = null)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $params = array(
            'provider' => $handle
            );

        if($namespace) {
            $params['namespace'] = $namespace;
        }


        $url = UrlHelper::getSiteUrl(craft()->config->get('actionTrigger').'/oauth/disconnect', $params);

        Craft::log(__METHOD__." : Deauthenticate : ".$url, LogLevel::Info, true);

        return $url;
    }

    // --------------------------------------------------------------------

    public function getAccount($handle, $namespace = null)
    {

        // get token

        if($namespace) {
            $tokenRecord = $this->_getTokenRecordByNamespace($handle, $namespace);
        } else {
            $tokenRecord = $this->_getTokenRecordByCurrentUser($handle);
        }

        if(!$tokenRecord) {
            return null;
        }

        $token = unserialize(base64_decode($tokenRecord->token));


        // provider

        $provider = craft()->oauth->getProvider($handle);

        $provider->setToken($token);

        return $provider->getAccount();
    }

    // --------------------------------------------------------------------

    public function getProvider($handle,  $configuredOnly = true)
    {
        $this->_loadProviders();

        $lcHandle = strtolower($handle);

        if($configuredOnly) {

            if(isset($this->_configuredProviders[$lcHandle])) {
                return $this->_configuredProviders[$lcHandle];
            }

        } else {

            if(isset($this->_allProviders[$lcHandle])) {
                return $this->_allProviders[$lcHandle];
            }

        }

        return null;
    }

    // --------------------------------------------------------------------

    public function getProviders($configuredOnly = true)
    {
        $this->_loadProviders();

        if($configuredOnly) {
            return $this->_configuredProviders;
        } else {
            return $this->_allProviders;
        }
    }

    // --------------------------------------------------------------------

    public function getToken($handle, $namespace = null, $userId = null)
    {
        $record = $this->_getTokenRecord($handle, $namespace, $userId);

        $model = Oauth_TokenModel::populateModel($record);

        return $model;
    }

    // --------------------------------------------------------------------

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

    // --------------------------------------------------------------------

    public function getSystemToken($handle, $namespace)
    {
        return $this->getToken($handle, $namespace);
    }

    // --------------------------------------------------------------------

    public function getSystemTokens()
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $conditions = 'namespace is not null';

        $params = array();

        $records = Oauth_TokenRecord::model()->findAll($conditions, $params);

        return Oauth_TokenModel::populateModels($records);
    }

    // --------------------------------------------------------------------

    public function getUserToken($handle, $userId = null)
    {
        $record = $this->_getTokenRecord($handle, null, $userId);

        if($record) {
            return Oauth_TokenModel::populateModel($record);
        }

        return null;
    }

    // --------------------------------------------------------------------

    public function getUserTokens($userId = null)
    {
        if(!$userId) {
            $userId = craft()->userSession->user->id;
        }

        if(!$userId) {
            return null;
        }

        $criteriaConditions = 'userId=:userId';
        $criteriaParams = array(':userId' => $userId);

        return Oauth_TokenRecord::model()->findAll($criteriaConditions, $criteriaParams);;
    }

    // --------------------------------------------------------------------

    public function providerSave(Oauth_ProviderModel $model)
    {
        // save record

        $record = $this->_getProviderRecordById($model->id);

        $record->class = $model->class;
        $record->clientId = $model->clientId;
        $record->clientSecret = $model->clientSecret;

        return $record->save(false);
    }

    // --------------------------------------------------------------------

    public function tokenDeleteById($id)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $record = Oauth_TokenRecord::model()->findByPk($id);

        if($record) {
            return $record->delete();
        }

        return false;
    }

    // --------------------------------------------------------------------

    public function tokenDeleteByNamespace($handle, $namespace)
    {
        $record = $this->_getTokenRecordByNamespace($handle, $namespace);

        if($record) {
            return $record->delete();
        }

        return false;
    }

    // --------------------------------------------------------------------

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

    // --------------------------------------------------------------------
    // Session
    // --------------------------------------------------------------------

    public function sessionAdd($k, $v = null)
    {
        $returnValue = craft()->httpSession->get($k);

        if(!$returnValue && $v) {
            $returnValue = $v;

            craft()->httpSession->add($k, $v);
        }

        return $returnValue;
    }

    // --------------------------------------------------------------------

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
    }

    // --------------------------------------------------------------------
    // Scope
    // --------------------------------------------------------------------

    public function scopeIsEnough($scope1, $scope2)
    {
        $scopeEnough = false;

        if(is_array($scope1) && is_array($scope2)) {

            $scopeEnough = true;

            foreach($scope1 as $s1) {

                $scopeFound = false;

                foreach($scope2 as $s2) {
                    if($s2 == $s1) {
                        $scopeFound = true;
                    }
                }

                if(!$scopeFound) {
                    $scopeEnough = false;
                    break;
                }
            }
        }

        return $scopeEnough;
    }

    // --------------------------------------------------------------------

    public function scopeMix($scope1, $scope2)
    {
        $scope = array();


        if(is_array($scope1)) {

            foreach($scope1 as $s1) {
                array_push($scope, $s1);
            }
        }

        if(is_array($scope2)) {

            foreach($scope2 as $s1) {

                $scopeFound = false;

                foreach($scope as $s2) {
                    if($s2 == $s1) {
                        $scopeFound = true;
                    }
                }

                if(!$scopeFound) {
                    array_push($scope, $s1);
                }
            }
        }

        if(!empty($scope)) {
            return $scope;
        }

        return null;
    }

    // --------------------------------------------------------------------

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

    // --------------------------------------------------------------------

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

        if($providerRecord) {
            return $providerRecord;
        }

        return null;
    }

    // --------------------------------------------------------------------

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

    // --------------------------------------------------------------------

    private function _getProviderRecords()
    {
        $records = Oauth_ProviderRecord::model()->findAll();

        return $records;
    }


    // --------------------------------------------------------------------

    private function _getTokenRecord($handle, $namespace = null, $userId = null)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        // get token

        if($namespace) {
            $tokenRecord = $this->_getTokenRecordByNamespace($handle, $namespace);
        } elseif($userId) {
            $tokenRecord = $this->_getTokenRecordByUser($handle, $userId);
        } else {
            $tokenRecord = $this->_getTokenRecordByCurrentUser($handle);
        }

        if(!$tokenRecord) {
            return null;
        }

        return $tokenRecord;
    }

    // --------------------------------------------------------------------

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


    // --------------------------------------------------------------------

    private function _getTokenRecordByUser($handle, $userId)
    {
        if(!craft()->userSession->user) {
            return null;
        }

        $conditions = 'provider=:provider';
        $params = array(':provider' => $handle);

        $conditions .= ' AND userId=:userId';
        $params[':userId'] = $userId;

        $tokenRecord = Oauth_TokenRecord::model()->find($conditions, $params);

        if($tokenRecord) {
            return $tokenRecord;
        }

        return null;
    }

    // --------------------------------------------------------------------

    private function _getTokenRecordByCurrentUser($handle)
    {
        if(!craft()->userSession->user) {
            return null;
        }

        $conditions = 'provider=:provider';
        $params = array(':provider' => $handle);

        $conditions .= ' AND userId=:userId';
        $params[':userId'] = craft()->userSession->user->id;

        $tokenRecord = Oauth_TokenRecord::model()->find($conditions, $params);

        if($tokenRecord) {
            return $tokenRecord;
        }

        return null;
    }

    // --------------------------------------------------------------------

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

    // --------------------------------------------------------------------

    /**
     * Loads the configured providers.
     */
    private function _loadProviders()
    {
        if($this->_providersLoaded) {

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

        foreach($providerSources as $providerSource) {


            $lcHandle = strtolower($providerSource->getHandle());

            $record = $this->_getProviderRecordByHandle($providerSource->getHandle());

            $provider = Oauth_ProviderModel::populateModel($record);
            $provider->class = $providerSource->getHandle();

            if($record) {
                $providerSource->setClient($provider->clientId, $provider->clientSecret);
                $provider->providerSource = $providerSource;
                $this->_configuredProviders[$lcHandle] = $provider;
            } else {
                $provider->providerSource = $providerSource;
            }

            $this->_allProviders[$lcHandle] = $provider;
        }

        $this->_providersLoaded = true;
    }

    // --------------------------------------------------------------------
}