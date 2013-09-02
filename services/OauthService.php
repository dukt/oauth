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
            $tokenRecord = craft()->oauth_tokens->tokenRecordByNamespace($providerClass, $namespace);
        } else {
            $tokenRecord = craft()->oauth_tokens->tokenRecordByCurrentUser($providerClass);
        }

        if(!$tokenRecord) {
            return null;
        }

        $token = unserialize(base64_decode($tokenRecord->token));


        // provider

        $provider = craft()->oauth->getProvider($providerClass);

        $provider->connect($token);

        return $provider->getAccount();
    }

    // --------------------------------------------------------------------

    public function getProvider($handle, $configuredOnly = true)
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

                $record = craft()->oauth_providers->providerRecord($handle);

                $provider->isConfigured = true;
                $provider->record = $record;
            } else {
                echo $handle;
            }

            return $provider;
        }
    }

    // --------------------------------------------------------------------

    public function getProviders($configuredOnly = true)
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

                    $provider = $this->getProvider($handle, false);

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

            $record = craft()->oauth_providers->providerRecord($handle);

            $provider->isConfigured = true;
            $provider->record = $record;
        }

        return $provider;
    }

    // --------------------------------------------------------------------

    public function getToken($providerClass, $namespace = null, $userId = null)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        // get token

        $tokenRecord = $this->getTokenRecord($providerClass, $namespace, $userId);

        if(!$tokenRecord) {
            return null;
        }

        $token = unserialize(base64_decode($tokenRecord->token));

        return $token;
    }

    // --------------------------------------------------------------------

    public function getTokenEncoded($encodedToken)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $criteriaConditions = '';
        $criteriaParams = array();

        $criteriaConditions = '
            token=:token
            ';

        $criteriaParams = array(
            ':token' => $encodedToken
            );

        $tokenRecord = Oauth_TokenRecord::model()->find($criteriaConditions, $criteriaParams);

        return $tokenRecord;
    }

    // --------------------------------------------------------------------

    public function getTokenRecord($providerClass, $namespace = null, $userId = null)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        // get token

        if($namespace) {
            $tokenRecord = craft()->oauth_tokens->tokenRecordByNamespace($providerClass, $namespace);
        } elseif($userId) {
            $tokenRecord = craft()->oauth_tokens->tokenRecordByUser($providerClass, $userId);
        } else {
            $tokenRecord = craft()->oauth_tokens->tokenRecordByCurrentUser($providerClass);
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

        $criteriaConditions = 'namespace is not null';
        $criteriaParams = array();

        return Oauth_TokenRecord::model()->findAll($criteriaConditions, $criteriaParams);
    }

    // --------------------------------------------------------------------

    public function getUserToken($providerClass, $userId = null)
    {
        return $this->getToken($providerClass, null, $userId);
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

}