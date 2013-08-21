<?php

namespace Craft;

require_once(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');

use ReflectionClass;
use Symfony\Component\Finder\Finder;

class OauthService extends BaseApplicationComponent
{
    // --------------------------------------------------------------------

    protected $providerRecord;

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

    public function connectCallback($providerClass)
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
    // Provider
    // --------------------------------------------------------------------


    // --------------------------------------------------------------------

    public function providerConnect($provider)
    {

        $returnProvider = null;

        try {
            Craft::log(__METHOD__." : Provider processing", LogLevel::Info, true);

            $returnProvider = $provider->process(function($url, $token = null) {

                if ($token) {
                    $_SESSION['token'] = base64_encode(serialize($token));
                }

                header("Location: {$url}");

                exit;

            }, function() {
                return unserialize(base64_decode($_SESSION['token']));
            });

        } catch(\Exception $e) {

            Craft::log(__METHOD__." : Provider process failed : ".$e->getMessage(), LogLevel::Error);

            return false;
        }

        // die();

        return $returnProvider;
    }

    // --------------------------------------------------------------------

    public function providerInstantiate($providerClass, $token = null, $scope = null, $callbackUrl = null)
    {
        // get provider record

        $providerRecord = $this->providerRecord($providerClass);


        if(!$callbackUrl) {
            $callbackUrl = UrlHelper::getSiteUrl(
                craft()->config->get('actionTrigger').'/oauth/public/connect',
                array('provider' => $providerClass)
            );
        }

        // provider options

        if($providerRecord) {
            $opts = array(
                'id' => $providerRecord->clientId,
                'secret' => $providerRecord->clientSecret,
                'redirect_url' => $callbackUrl
            );
        } else {
            $opts = array(
                'id' => 'x',
                'secret' => 'x',
                'redirect_url' => 'x'
            );
        }

        if($scope) {
            if(is_array($scope) && !empty($scope)) {
                $opts['scope'] = $scope;
            }
        }


        $class = "\\OAuth\\Provider\\{$providerClass}";

        $provider = new $class($opts);

        if($token) {
            $provider->setToken($token);

            $this->tokenRefresh($provider);
        }

        return $provider;
    }

    // --------------------------------------------------------------------

    public function providerRecord($providerClass)
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

    public function providerIsConfigured($providerClass)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $record = Oauth_ProviderRecord::model()->find('providerClass=:providerClass', array(':providerClass' => $providerClass));

        if ($record) {
            Craft::log(__METHOD__." : Yes", LogLevel::Info, true);
            if(!empty($record->clientId) && !empty($record->clientSecret)) {
                return true;
            }
        }

        Craft::log(__METHOD__." : No", LogLevel::Info, true);

        return false;
    }

    // --------------------------------------------------------------------

    public function providerIsConnected($providerClass, $scope = null, $namespace = null)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $criteriaConditions = 'provider=:provider';
        $criteriaParams = array(':provider' => $providerClass);

        if(!$namespace) {
            $userId = craft()->userSession->user->id;

            $criteriaConditions .= ' AND userId=:userId';
            $criteriaParams[':userId'] = $userId;

        } else {
            $criteriaConditions .= ' AND namespace=:namespace';
            $criteriaParams[':namespace'] = $namespace;
        }

        $tokenRecord = Oauth_TokenRecord::model()->find($criteriaConditions, $criteriaParams);

        if($tokenRecord) {
            Craft::log(__METHOD__." : Token Record found", LogLevel::Info, true);

            // check scope (scopeIsEnough)

            return $this->scopeIsEnough($scope, $tokenRecord->scope);
        }

        Craft::log(__METHOD__." : Token Record not found", LogLevel::Info, true);

        return false;
    }

    // --------------------------------------------------------------------
    // used by :
    // analytics settings
    // oauth edit provider

    public function providerSave(Oauth_ProviderModel &$model)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $class = $model->getAttribute('providerClass');

        if (null === ($record = Oauth_ProviderRecord::model()->find('providerClass=:providerClass', array(':providerClass' => $class)))) {
            $record = $this->providerRecord->create();
        }

        $record->setAttributes($model->getAttributes());

        if ($record->save()) {
            // update id on model (for new records)

            $model->setAttribute('id', $record->getAttribute('id'));

            // Connect Service

           // $this->connectService($record);

            return true;
        } else {

            $model->addErrors($record->getErrors());

            return false;
        }
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
    // token(s)
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

    public function tokenScopeByCurrentUser($providerClass)
    {
        // provider record

        $providerRecord = $this->providerRecord($providerClass);

        if($providerRecord) {

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

    public function tokenScopeByNamespace($providerClass, $namespace)
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

    public function tokenRecordByCurrentUser($providerClass)
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

    public function tokenRecordByNamespace($providerClass, $namespace)
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

    public function tokenRefresh($provider)
    {
        $difference = ($provider->token->expires - time());

        // token expired : we need to refresh it

        if($difference < 1) {

            Craft::log(__METHOD__." : Refresh token ", LogLevel::Info, true);

            $encodedToken = base64_encode(serialize($provider->token));

            $tokenRecord = craft()->oauth->getToken($encodedToken);


            if(method_exists($provider, 'access') && $provider->token->refresh_token) {

                $accessToken = $provider->access($provider->token->refresh_token, array('grant_type' => 'refresh_token'));

                if(!$accessToken) {
                    Craft::log(__METHOD__." : Could not refresh token", LogLevel::Info, true);
                }
                // save token

                $provider->token->access_token = $accessToken->access_token;
                $provider->token->expires = $accessToken->expires;

                $tokenRecord->token = base64_encode(serialize($provider->token));

                if($tokenRecord->save()) {
                    Craft::log(__METHOD__." : Token saved", LogLevel::Info, true);
                }
            } else {
                Craft::log(__METHOD__." : Access method (for refresh) doesn't exists for this provider", LogLevel::Info, true);
            }
        }
    }

    // --------------------------------------------------------------------
    // used by :
    // analytics
    // oauth

    public function tokenReset($providerClass)
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

    public function tokensByProvider($provider, $user = false)
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

    public function getAccount($providerClass, $namespace = null)
    {

        // get token

        if($namespace) {
            $tokenRecord = craft()->oauth->tokenRecordByNamespace($providerClass, $namespace);
        } else {
            $tokenRecord = craft()->oauth->tokenRecordByCurrentUser($providerClass);
        }

        if(!$tokenRecord) {
            return null;
        }

        $token = unserialize(base64_decode($tokenRecord->token));


        // provider

        $provider = craft()->oauth->providerInstantiate($providerClass, $token);

        return $provider->getUserInfo();
    }

    // --------------------------------------------------------------------

    public function getProvider($providerClass)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        // get the option

        $record = Oauth_ProviderRecord::model()->find('providerClass=:providerClass', array(':providerClass' => $providerClass));

        if ($record) {

            return Oauth_ProviderModel::populateModel($record);
        }

        return new Oauth_ProviderModel();
    }

    // --------------------------------------------------------------------

    public function getProviders($configuredOnly = true)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);



        // retrieve provider class files

        $result = array(
                'Facebook' => array('class' => 'Facebook', 'isConfigured' => false),
                'Google' => array('class' => 'Google', 'isConfigured' => false),
                'Github' => array('class' => 'Github', 'isConfigured' => false),
                'Twitter' => array('class' => 'Twitter', 'isConfigured' => false),
                'Flickr' => array('class' => 'Flickr', 'isConfigured' => false)
            );

        ksort($result);

        // get provider records and mix with result

        $conditions = '';
        $params = array();

        $providerRecords = Oauth_ProviderRecord::model()->findAll($conditions, $params);

        if($providerRecords) {
            foreach($providerRecords as $providerRecord) {

                if(isset($result[$providerRecord->providerClass])) {
                    $result[$providerRecord->providerClass]['isConfigured'] = true;
                    $result[$providerRecord->providerClass]['record'] = $providerRecord;
                }
            }
        }


        // filter configured providers

        if($configuredOnly) {
            foreach($result as $k => $v)
            {
                if(!$v['isConfigured']) {
                    unset($result[$k]);
                }
            }
        }

        return $result;
    }

    // --------------------------------------------------------------------

    public function getToken($encodedToken)
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

    public function getTokens($namespace = null)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $criteriaConditions = '';
        $criteriaParams = array();

        if($namespace) {
            $criteriaConditions = '
                namespace=:namespace
                ';

            $criteriaParams = array(
                ':namespace' => $namespace
                );
        }

        $tokens = Oauth_TokenRecord::model()->findAll($criteriaConditions, $criteriaParams);

        return $tokens;
    }

    // --------------------------------------------------------------------
}