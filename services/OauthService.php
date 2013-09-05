<?php

namespace Craft;

require_once(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');

use ReflectionClass;
use Symfony\Component\Finder\Finder;

class OauthService extends BaseApplicationComponent
{
    // --------------------------------------------------------------------

    protected $providerRecord;

    private $_enableProviders = array();
    private $_allProviders = array();

    // --------------------------------------------------------------------

    public function __construct($providerRecord = null)
    {
        $this->providerRecord = $providerRecord;

        if (is_null($this->providerRecord)) {
            $this->providerRecord = Oauth_ProviderRecord::model();
        }
    }

    // --------------------------------------------------------------------

    public function providerSave(Oauth_ProviderModel $model)
    {
        // save record

        $record = $this->_getProviderRecordById($model->id);

        $record->providerClass = $model->providerClass;
        $record->enabled = $model->enabled;
        $record->clientId = $model->clientId;
        $record->clientSecret = $model->clientSecret;

        return $record->save(false);
    }


    // --------------------------------------------------------------------

    public function tokenSave(Oauth_TokenModel $model)
    {
        // save record

        $record = $this->_getTokenRecordById($model->id);

        $record->userId = $model->userId;
        $record->provider = $model->provider;
        $record->userMapping = $model->userMapping;
        $record->token = $model->token;
        $record->scope = $model->scope;

        return $record->save(false);
    }

    // --------------------------------------------------------------------

    public function connect($providerClass, $scope = null, $namespace = null)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $params = array('provider' => $providerClass);

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

    public function callbackUrl($providerClass)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $params = array('provider' => $providerClass);

        $url = UrlHelper::getSiteUrl(craft()->config->get('actionTrigger').'/oauth/public/connect', $params);

        Craft::log(__METHOD__." : Authenticate : ".$url, LogLevel::Info, true);

        return $url;
    }

    // --------------------------------------------------------------------

    public function disconnect($providerClass, $namespace = null)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $params = array(
            'provider' => $providerClass
            );

        if($namespace) {
            $params['namespace'] = $namespace;
        }


        $url = UrlHelper::getSiteUrl(craft()->config->get('actionTrigger').'/oauth/disconnect', $params);

        Craft::log(__METHOD__." : Deauthenticate : ".$url, LogLevel::Info, true);

        return $url;
    }

    // --------------------------------------------------------------------

    public function getAccount($providerClass, $namespace = null)
    {

        // get token

        if($namespace) {
            $tokenRecord = $this->_tokenRecordByNamespace($providerClass, $namespace);
        } else {
            $tokenRecord = $this->_tokenRecordByCurrentUser($providerClass);
        }

        if(!$tokenRecord) {
            return null;
        }

        $token = unserialize(base64_decode($tokenRecord->token));


        // provider

        $providerSource = craft()->oauth->getProviderSource($providerClass);

        $providerSource->connect($token);

        return $providerSource->getAccount();
    }

    // --------------------------------------------------------------------

    public function getProvider($handle)
    {
        $record = $this->_getProviderRecord($handle);

        $model = Oauth_ProviderModel::populateModel($record);

        return $model;
    }

    // --------------------------------------------------------------------

    public function getProviderSource($handle, $configuredOnly = true)
    {
        if($configuredOnly) {

            $this->_loadProviders();

            if(isset($this->_enableProviders[$handle])) {
                return $this->_enableProviders[$handle];
            } else {
                return null;
            }

        } else {
            // get provider class


            $provider = $this->_getProvider($handle);

            if($provider) {

                // load record data into the provider

                $record = $this->_getProviderRecord($handle);

                if($record) {
                    $provider->isConfigured = true;
                }
            } else {
                echo $handle;
            }

            return $provider;
        }
    }

    // --------------------------------------------------------------------

    public function getProviderSources($configuredOnly = true)
    {

        if($configuredOnly) {
            $this->_loadProviders();

            return $this->_enableProviders;
        } else {

            $providersPath = CRAFT_PLUGINS_PATH.'oauth/providers/OAuthProviders';
            $providersFolderContents = IOHelper::getFolderContents($providersPath, false);

            if($providersFolderContents) {

                foreach($providersFolderContents as $path) {
                    $path = IOHelper::normalizePathSeparators($path);
                    $fileName = IOHelper::getFileName($path, false);

                    if($fileName == 'BaseOAuthProvider') continue;

                    // Chop off the "OAuthProvider" suffix
                    $handle = substr($fileName, 0, strlen($fileName) - 13);

                    $provider = $this->getProviderSource($handle, false);

                    $this->_allProviders[$handle] = $provider;
                }
            }

            return $this->_allProviders;
        }
    }


    // --------------------------------------------------------------------

    /**
     * Loads the configured (enabled) plugins.
     */
    private function _loadProviders()
    {
        // get configured provider records

        $conditions = 'clientId is not null AND clientSecret is not null';
        $params = array();

        $providerRecords = Oauth_ProviderRecord::model()->findAll($conditions, $params);

        foreach($providerRecords as $row) {

            $class = $row['providerClass'];
            $handle = $row['providerClass'];

            $provider = $this->_getProvider($class);

            if($provider) {
                $this->_enableProviders[$handle] = $provider;
            }
        }
    }

    // --------------------------------------------------------------------

    private function _getProvider($handle)
    {
        // Get the full class name

        $class = $handle.'OAuthProvider';

        $nsClass = 'OAuthProviders\\'.$class;


        // Skip the autoloader

        if (!class_exists($nsClass, false))
        {
            $path = CRAFT_PLUGINS_PATH.'oauth/providers/OAuthProviders/'.$class.'.php';

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


        $provider = new $nsClass;

        if (!$provider instanceof \OAuthProviders\BaseOAuthProvider)
        {
            die("this provider doesn't implement BaseOAuthProvider abstract class");
        }

        if($provider) {

            // load record data into the provider

            $record = $this->_getProviderRecord($handle);

            if($record) {
                $provider->isConfigured = true;

            }
        }

        return $provider;
    }

    // --------------------------------------------------------------------

    public function getToken($providerClass, $namespace = null, $userId = null)
    {
        $record = $this->getTokenRecord($providerClass, $namespace, $userId);

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

    public function getTokenRecord($providerClass, $namespace = null, $userId = null)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        // get token

        if($namespace) {
            $tokenRecord = $this->_tokenRecordByNamespace($providerClass, $namespace);
        } elseif($userId) {
            $tokenRecord = $this->_tokenRecordByUser($providerClass, $userId);
        } else {
            $tokenRecord = $this->_tokenRecordByCurrentUser($providerClass);
        }

        if(!$tokenRecord) {
            return null;
        }

        return $tokenRecord;
    }

    // --------------------------------------------------------------------

    public function getSystemToken($providerClass, $namespace)
    {
        return $this->getToken($providerClass, $namespace);
    }

    // --------------------------------------------------------------------

    public function getSystemTokens()
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $conditions = 'namespace is not null';

        $params = array();

        return Oauth_TokenRecord::model()->findAll($conditions, $params);
    }

    // --------------------------------------------------------------------

    public function getUserToken($providerClass, $userId = null)
    {
        $record = $this->getTokenRecord($providerClass, null, $userId);

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
    // httpSession
    // --------------------------------------------------------------------

    public function httpSessionAdd($k, $v = null)
    {
        $returnValue = craft()->httpSession->get($k);

        if(!$returnValue && $v) {
            $returnValue = $v;

            craft()->httpSession->add($k, $v);
        }

        return $returnValue;
    }

    // --------------------------------------------------------------------

    public function httpSessionClean()
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

    public function _getProviderRecord($providerClass)
    {
        $providerRecord = Oauth_ProviderRecord::model()->find(

            // conditions

            'providerClass=:provider',


            // params

            array(
                ':provider' => $providerClass
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

    public function tokenDeleteByNamespace($providerClass, $namespace)
    {
        $record = $this->_tokenRecordByNamespace($providerClass, $namespace);

        if($record) {
            return $record->delete();
        }

        return false;
    }


    // --------------------------------------------------------------------

    public function _tokenScopeByCurrentUser($providerClass)
    {
        // provider record

        $provider = craft()->oauth->getProvider($providerClass);

        if($provider) {

            // user token record

            $tokenRecord = $this->tokenRecordByCurrentUser($providerClass);

            if($tokenRecord) {

                $tokenScope = @unserialize(base64_decode($tokenRecord->scope));

                return $tokenScope;
            }
        }

        return null;
    }

    // --------------------------------------------------------------------
    // Deprecated ?
    // --------------------------------------------------------------------

    public function _tokenScopeByNamespace($providerClass, $namespace)
    {
        // provider record

        $providerRecord = $this->providerRecord($providerClass);

        if($providerRecord) {

            // user token record

            $tokenRecord = $this->tokenRecordByNamespace($providerClass, $namespace);

            if($tokenRecord) {

                $tokenScope = @unserialize(base64_decode($tokenRecord->scope));

                return $tokenScope;
            }
        }

        return null;
    }

    // --------------------------------------------------------------------

    public function _tokenRecordByUser($providerClass, $userId)
    {
        if(!craft()->userSession->user) {
            return null;
        }

        $conditions = 'provider=:provider';
        $params = array(':provider' => $providerClass);

        $conditions .= ' AND userId=:userId';
        $params[':userId'] = $userId;

        $tokenRecord = Oauth_TokenRecord::model()->find($conditions, $params);

        if($tokenRecord) {
            return $tokenRecord;
        }

        return null;
    }

    // --------------------------------------------------------------------

    public function _tokenRecordByCurrentUser($providerClass)
    {
        if(!craft()->userSession->user) {
            return null;
        }

        $conditions = 'provider=:provider';
        $params = array(':provider' => $providerClass);

        $conditions .= ' AND userId=:userId';
        $params[':userId'] = craft()->userSession->user->id;

        $tokenRecord = Oauth_TokenRecord::model()->find($conditions, $params);

        if($tokenRecord) {
            return $tokenRecord;
        }

        return null;
    }

    // --------------------------------------------------------------------

    public function _tokenRecordByNamespace($providerClass, $namespace)
    {
        $conditions = 'provider=:provider AND namespace=:namespace';
        $params = array(
                ':provider' => $providerClass,
                ':namespace' => $namespace,
            );

        $tokenRecord = Oauth_TokenRecord::model()->find($conditions, $params);

        return $tokenRecord;
    }

    // --------------------------------------------------------------------
    // deprecated ?
    // --------------------------------------------------------------------

    public function _tokenReset($providerClass)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $providerClass = craft()->request->getParam('providerClass');

        $record = Oauth_ProviderRecord::model()->find('providerClass=:providerClass', array(':providerClass' => $providerClass));

        if($record)
        {
            $record->token = NULL;
            return $record->save();
        }

        return false;
    }

    // --------------------------------------------------------------------

    public function _tokensByProvider($provider, $user = false)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        if($user) {
            // $userId = craft()->userSession->user->id;

            $userId = $user;

            $criteriaConditions = '
                provider=:provider AND
                userId=:userId
                ';

            $criteriaParams = array(
                ':userId' => $userId,
                ':provider' => $provider,
                );
        } else {
            $criteriaConditions = '
                provider=:provider
                ';

            $criteriaParams = array(
                ':provider' => $provider
                );
        }

        $tokens = Oauth_TokenRecord::model()->findAll($criteriaConditions, $criteriaParams);

        return $tokens;
    }

    // --------------------------------------------------------------------
}