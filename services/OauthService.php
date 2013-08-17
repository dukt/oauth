<?php

namespace Craft;

require_once(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');

use ReflectionClass;
use Symfony\Component\Finder\Finder;

class OauthService extends BaseApplicationComponent
{
    // --------------------------------------------------------------------

    protected $serviceRecord;

    // --------------------------------------------------------------------

    public function __construct($serviceRecord = null)
    {
        $this->serviceRecord = $serviceRecord;
        if (is_null($this->serviceRecord)) {
            $this->serviceRecord = Oauth_ProviderRecord::model();
        }
    }

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
    }

    // --------------------------------------------------------------------

    public function connect($providerClass, $scope = null, $namespace = null, $userMode = false)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $params = array('provider' => $providerClass);

        if($scope) {
            $params['scope'] = base64_encode(serialize($scope));
        }

        if($namespace) {
            $params['namespace'] = $namespace;
        }

        if($userMode === true) {
            $params['userMode'] = $userMode;
        }

        $url = UrlHelper::getSiteUrl(craft()->config->get('actionTrigger').'/oauth/public/connect', $params);

        Craft::log(__METHOD__." : Authenticate : ".$url, LogLevel::Info, true);

        return $url;
    }

    // --------------------------------------------------------------------

    public function disconnect($namespace, $providerClass)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $params = array(
                    'provider' => $providerClass
                    );


        $url = UrlHelper::getSiteUrl(craft()->config->get('actionTrigger').'/oauth/public/disconnect', $params);

        Craft::log(__METHOD__." : Deauthenticate : ".$url, LogLevel::Info, true);

        return $url;
    }


    // --------------------------------------------------------------------

    public function userTokenRecord($providerClass)
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

    public function userTokenRecordFromMapping($providerClass, $userMapping)
    {
        if(!craft()->userSession->user) {
            return null;
        }

        $conditions = 'provider=:provider';
        $params = array(':provider' => $providerClass);

        // $conditions .= ' AND userId=:userId';
        // $params[':userId'] = craft()->userSession->user->id;

        $conditions .= ' AND userMapping=:userMapping';
        $params[':userMapping'] = $userMapping;

        $tokenRecord = Oauth_TokenRecord::model()->find($conditions, $params);

        if($tokenRecord) {
            return $tokenRecord;
        }

        return null;
    }

    // --------------------------------------------------------------------

    private function currentUser()
    {
        if(craft()->userSession->user) {
            return craft()->userSession->user;
        }

        return null;
    }

    // --------------------------------------------------------------------

    public function userTokenScope($providerClass)
    {
        // provider record

        $providerRecord = $this->providerRecord($providerClass);

        if($providerRecord) {

            // user token record

            $tokenRecord = $this->userTokenRecord($providerClass);

            if($tokenRecord) {

                $tokenScope = @unserialize(base64_decode($tokenRecord->scope));

                return $tokenScope;
            }
        }

        return null;
    }

    // --------------------------------------------------------------------

    public function systemTokenRecord($providerClass, $namespace)
    {
        $tokenRecord = Oauth_TokenRecord::model()->find(

            // conditions

            'provider=:provider AND namespace=:namespace',

            
            // params

            array(
                ':provider' => $providerClass,
                ':namespace' => $namespace,
            )
        );

        if($tokenRecord) {
            return $tokenRecord;
        }

        return null;
    }

    // --------------------------------------------------------------------

    public function systemTokenScope($providerClass, $namespace)
    {
        // provider record

        $providerRecord = $this->providerRecord($providerClass);

        if($providerRecord) {

            // user token record

            $tokenRecord = craft()->oauth->systemTokenRecord($providerClass, $namespace);

            if($tokenRecord) {

                $tokenScope = @unserialize(base64_decode($tokenRecord->scope));

                return $tokenScope;
            }
        }

        return null;
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

    public function providerIsConnected($providerClass, $scope = null, $namespace = null, $userMode = false)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $criteriaConditions = 'provider=:provider';
        $criteriaParams = array(':provider' => $providerClass);

        if($userMode) {
            $userId = craft()->userSession->user->id;
            
            $criteriaConditions .= ' AND userId=:userId';
            $criteriaParams[':userId'] = $userId;

        } else {

            if($namespace) {
                $criteriaConditions .= ' AND namespace=:namespace';
                $criteriaParams[':namespace'] = $namespace;
            }
        }

        $tokenRecord = Oauth_TokenRecord::model()->find($criteriaConditions, $criteriaParams);

        if($tokenRecord) {
            Craft::log(__METHOD__." : Token Record found", LogLevel::Info, true);

            // check scope (isScopeEnough)

            return $this->isScopeEnough($scope, $tokenRecord->scope);
        }

        Craft::log(__METHOD__." : Token Record not found", LogLevel::Info, true);

        return false;
    }

    // --------------------------------------------------------------------

    public function isScopeEnough($scope1, $scope2)
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

    public function mixScopes($scope1, $scope2)
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

    public function providerCallbackUrl($providerClass)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $params = array('provider' => $providerClass);

        $url = UrlHelper::getSiteUrl(craft()->config->get('actionTrigger').'/oauth/public/connect', $params);

        Craft::log(__METHOD__." : Authenticate : ".$url, LogLevel::Info, true);

        return $url;
    }

    // --------------------------------------------------------------------

    public function getProviderLibrary($providerClass, $namespace = null, $userMode = false)
    {

        Craft::log(__METHOD__, LogLevel::Info, true);

        // if($namespace == null)
        // {
        //     Craft::log(__METHOD__." : Namespace null : Dummy provider", LogLevel::Info, true);
        //     $class = "\\Dukt\\Connect\\$providerClass\\Provider";
        //     $opts = array('id' => 'x', 'secret' => 'x', 'redirect_url' => 'x');
        //     $provider = new $class($opts);
        //     return $provider;
        // }

        $criteriaConditions = 'provider=:provider';
        $criteriaParams = array(
                ':provider' => $providerClass
            );

        if($namespace) {
            if($namespace == '*') {
                $criteriaConditions .= ' AND namespace is not null';
            } else {
                $criteriaConditions .= ' AND namespace=:namespace';
                $criteriaParams[':namespace'] = $namespace;
            }
        }
        
        if($userMode) {
            if(craft()->userSession->user) {
                $criteriaConditions .= ' AND userId=:userId';
                $criteriaParams[':userId'] = craft()->userSession->user->id;
            } else {
                return NULL;
            }
        }

        $tokenRecord = Oauth_TokenRecord::model()->find($criteriaConditions, $criteriaParams);

        $token = @unserialize(base64_decode($tokenRecord->token));

        if(!$token) {
            Craft::log(__METHOD__." : Token null", LogLevel::Info, true);
            return NULL;
        }


        // Create the OAuth provider

        $providerClass = $tokenRecord->provider;

        $providerRecord = Oauth_ProviderRecord::model()->find('providerClass=:providerClass', array(':providerClass' => $providerClass));

        $opts = array(
            'id' => $providerRecord->clientId,
            'secret' => $providerRecord->clientSecret,
            'redirect_url' => \Craft\UrlHelper::getActionUrl('oauth/public/connect/', array('provider' => $providerClass))
        );

        $class = "\\Dukt\\Connect\\$providerClass\\Provider";
        $provider = new $class($opts);
        $provider->setToken($token);
        // var_dump($provider);
        // die();

        return $provider;
    }

    // --------------------------------------------------------------------

    public function getAccount($providerClass, $namespace = null, $userMode = false)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $provider = $this->getProviderLibrary($providerClass, $namespace, $userMode);

        // var_dump($providerClass, $namespace, $userMode);
        // die();

        if(!$provider) {
            Craft::log(__METHOD__." : Provider null ", LogLevel::Info, true);
            return NULL;
        }


        // token expired : we need to refresh it
        $difference = ($provider->token->expires - time());

        // var_dump($provider->token);
        // var_dump($difference);

        // die();
        if($difference < 1)
        {
            // echo $providerClass;
            // var_dump($provider->token);
            // var_dump($difference);
            // echo '<hr />';
            // return;
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
                Craft::log(__METHOD__." : Access method (for refresh) doesn't exists for ".$providerClass, LogLevel::Info, true);
            }
        } else {
            // echo $providerClass;
            // var_dump($provider->token);
            // var_dump($difference);
            // echo '<hr />';
        }

        $account = $provider->getAccount();

        if(!$account) {
            Craft::log(__METHOD__." : Account null", LogLevel::Info, true);
            return NULL;
        }

        return $account;
    }

    // --------------------------------------------------------------------

    public function enable($id)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $record = $this->getServiceById($id);
        $record->enabled = true;
        $record->save();

        return true;
    }

    // --------------------------------------------------------------------

    public function disable($id)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $record = $this->getServiceById($id);
        $record->enabled = false;
        $record->save();

        return true;
    }

    // --------------------------------------------------------------------

    public function getProvider($providerClass)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        // get the option

        $record = Oauth_ProviderRecord::model()->find('providerClass=:providerClass', array(':providerClass' => $providerClass));

        if ($record) {

            return Oauth_ServiceModel::populateModel($record);
        }

        return new Oauth_ServiceModel();
    }

    // --------------------------------------------------------------------

    public function getServiceById($id)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $record = $this->serviceRecord->findByPk($id);

        if ($record) {

            return $record;
        }

        return new Oauth_ServiceModel();
    }

    // --------------------------------------------------------------------

    public function saveService(Oauth_ServiceModel &$model)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $class = $model->getAttribute('providerClass');

        if (null === ($record = Oauth_ProviderRecord::model()->find('providerClass=:providerClass', array(':providerClass' => $class)))) {
            $record = $this->serviceRecord->create();
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

    public function newService($attributes = array())
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $model = new Oauth_ServiceModel();

        $model->setAttributes($attributes);

        return $model;
    }

    // --------------------------------------------------------------------

    public function connectService($record = false)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        if(!$record)
        {
            Craft::log(__METHOD__." : Record false", LogLevel::Info, true);

            $serviceId = craft()->request->getParam('id');

            $record = $this->serviceRecord->findByPk($serviceId);
        }


        $className = $record->className;

        $redirectUrl = \Craft\UrlHelper::getActionUrl('campaigns/settings/serviceCallback/', array('id' => $record->id));

        $provider = \OAuth\OAuth::provider($className, array(
            'id' => $record->clientId,
            'secret' => $record->clientSecret,
            'redirect_url' => $redirectUrl
        ));

        Craft::log(__METHOD__." : Provider process redirect_url :".$redirectUrl, LogLevel::Info, true);

        $provider = $provider->process(function($url, $token = null) {

            if ($token) {
                $_SESSION['token'] = base64_encode(serialize($token));
            }

            Craft::log(__METHOD__." : Provider processing header location : {$url}", LogLevel::Info, true);

            header("Location: {$url}");

            exit;

        }, function() {
            return unserialize(base64_decode($_SESSION['token']));
        });


        $token = $provider->token();

        $record->token = base64_encode(serialize($token));

        $record->save();
    }

    // --------------------------------------------------------------------

    public function service($id)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $service = $this->serviceRecord->findByPk($id);


        $providerParams = array();
        $providerParams['id'] = $service->clientId;
        $providerParams['secret'] = $service->clientSecret;
        $providerParams['redirect_url'] = "http://google.fr";

        try {
            Craft::log(__METHOD__." : Creating oauth provider", LogLevel::Info, true);

            $provider = \OAuth\OAuth::provider($service->providerClass, $providerParams);

            if(!$provider) {
                Craft::log(__METHOD__." : Provider null", LogLevel::Info, true);
            }

            $token = unserialize(base64_decode($service->token));

            // refresh token if needed ?

            if(!$token)
            {
                Craft::log(__METHOD__." : Invalid token", LogLevel::Info, true);

                throw new \Exception('Invalid Token');
            }

            $provider->setToken($token);

        } catch(\Exception $e)
        {
            Craft::log(__METHOD__." : ".'Provider couln\'t instantiate : '.$e->getMessage(), LogLevel::Info, true);
            throw new Exception('Provider couln\'t instantiate : '.$e->getMessage());
        }

        // $serviceClassName = 'Dukt\\Connect\\'.$service->providerClass.'\\Service';

        // $serviceObject = new $serviceClassName($provider);

        $serviceObject = $provider;

        return $serviceObject;
    }

    // --------------------------------------------------------------------

    public function serviceSend($serviceId, $method, $params = array())
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $service = $this->serviceRecord->findByPk($serviceId);


        $providerParams = array();
        $providerParams['id'] = $service->clientId;
        $providerParams['secret'] = $service->clientSecret;
        $providerParams['redirect_url'] = "http://google.fr";

        try {
            Craft::log(__METHOD__." : Creating oauth provider", LogLevel::Info, true);

            $provider = \OAuth\OAuth::provider($service->className, $providerParams);

            $token = unserialize(base64_decode($service->token));

            // refresh token if needed ?

            if(!$token)
            {
                Craft::log(__METHOD__." : Invalid token", LogLevel::Info, true);

                throw new \Exception('Invalid Token');
            }

            $provider->setToken($token);

        } catch(\Exception $e)
        {
            Craft::log(__METHOD__." : ".'Provider couln\'t instantiate : '.$e->getMessage(), LogLevel::Info, true);

            throw new Exception('Provider couln\'t instantiate');
        }

        $serviceClassName = 'Dukt\\Campaigns\\Services\\'.$service->className;

        $request = new $serviceClassName($provider);

        return $request->send($method, $params);

        //return $request;
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

    public function getTokensByProvider($provider, $user = false)
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

    public function getUserToken($providerClass)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        if(!craft()->userSession->user) {
            return null;
        }

        $criteriaConditions = '
            provider=:provider AND userId=:userId
            ';

        $criteriaParams = array(
            ':provider' => $providerClass,
            ':userId' => craft()->userSession->user->id
            );

        $tokenRecord = Oauth_TokenRecord::model()->find($criteriaConditions, $criteriaParams);

        return $tokenRecord;
    }


    // --------------------------------------------------------------------

    public function getSystemToken($providerClass, $namespace)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $criteriaConditions = '
            provider=:provider AND namespace=:namespace
            ';

        $criteriaParams = array(
            ':provider' => $providerClass,
            ':namespace' => $namespace
            );

        $tokenRecord = Oauth_TokenRecord::model()->find($criteriaConditions, $criteriaParams);

        return $tokenRecord;
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

    public function getProviders()
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $directory = CRAFT_PLUGINS_PATH.'oauth/libraries/Dukt/Connect/';

        $result = array();

        $finder = new Finder();

        $files = $finder->directories()->depth(0)->in($directory);

        foreach($files as $file)
        {
            $class = $file->getRelativePathName();

            //$class = substr($class, 0, -4);

            switch($class)
            {
                case "Common":

                break;

                default:
                $result[$class] = $class;
            }
        }

        return $result;

    }

    // --------------------------------------------------------------------

    public function getAllServices()
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $records = $this->serviceRecord->findAll(array('order'=>'t.title'));

        return Campaigns_ServiceModel::populateModels($records, 'id');
    }

    // --------------------------------------------------------------------

    public function deleteServiceById($id)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        return $this->serviceRecord->deleteByPk($id);
    }

    // --------------------------------------------------------------------

    public function deleteTokenById($id)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $record = Oauth_TokenRecord::model()->findByPk($id);

        if($record) {
            return $record->delete();
        }

        return false;
    }

    // --------------------------------------------------------------------

    public function resetServiceToken($providerClass)
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

}

