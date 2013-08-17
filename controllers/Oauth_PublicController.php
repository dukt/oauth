<?php

namespace Craft;

require(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');

class Oauth_PublicController extends BaseController
{
    protected $allowAnonymous = true;

    // --------------------------------------------------------------------

    public function actionConnect()
    {
        // craft()->oauth->httpSessionClean();

        $userMode = (bool) craft()->request->getParam('userMode');
        $providerClass = craft()->request->getParam('provider');
        
        craft()->oauth->httpSessionAdd('oauth.providerClass', $providerClass);
        craft()->oauth->httpSessionAdd('oauth.userMode', $userMode);
        craft()->oauth->httpSessionAdd('oauth.referer', (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null));


        // connect user or system

        if(craft()->httpSession->get('oauth.userMode')) {
            $this->actionConnectUser();
        } else {
            $this->actionConnectSystem();
        }
    }

    // --------------------------------------------------------------------

    public function actionConnectUser()
    {
        // get providerClass

        $providerClass = craft()->httpSession->get('oauth.providerClass');


        // session variables

        $social         = craft()->httpSession->get('oauth.social');
        $socialCallback = craft()->httpSession->get('oauth.socialCallback');
        $referer        = craft()->httpSession->get('oauth.referer');
        $scope          = craft()->httpSession->get('oauth.scope');


        // get scope
        
        $scope = craft()->httpSession->get('oauth.scope');

        if(!$scope) {

            // scopeParam

            $scopeParam = craft()->request->getParam('scope');
            $scopeParam = unserialize(base64_decode($scopeParam));


            // scopeToken

            $scopeToken = craft()->oauth->userTokenScope($providerClass);


            // is scope enough ? 

            $scopeEnough = craft()->oauth->isScopeEnough($scopeParam, $scopeToken);


            // scope is not enough, connect user with new scope

            if(!$scopeEnough) {
                $scope = craft()->oauth->mixScopes($scopeParam, $scopeToken);

                craft()->httpSession->add('oauth.scope', $scope);
            }
        }
        

        // instantiate provider

        $callbackUrl = UrlHelper::getSiteUrl(
            craft()->config->get('actionTrigger').'/oauth/public/connect',
            array('provider' => $providerClass)
        );

        $provider = craft()->oauth->instantiateProvider($providerClass, $callbackUrl, null, $scope);


        // connect provider

        $provider = craft()->oauth->connectProviderObject($provider);


        // post-connect

        if($provider) {

            // token

            $token = $provider->token();
            $token = base64_encode(serialize($token));


            // ----------------------
            // social bypass
            // ----------------------

            if($social) {
                craft()->httpSession->add('oauth.token', $token);

                $this->redirect($socialCallback);

                return;
            }


            // ----------------------
            // save userToken record
            // ----------------------

            if(!craft()->userSession->user) {
                return null;
            }


            // set default scope

            if(!$scope) {
                $scope = $provider->scope;
            }


            // get token record

            $criteriaConditions = '
                provider=:provider AND userId=:userId
                ';

            $criteriaParams = array(
                ':provider' => $providerClass,
                ':userId' => craft()->userSession->user->id
                );

            $tokenRecord = Oauth_TokenRecord::model()->find($criteriaConditions, $criteriaParams);

            // or create a new one

            if(!$tokenRecord) {

                $tokenRecord = new Oauth_TokenRecord();
                $tokenRecord->userId = craft()->userSession->user->id;
                $tokenRecord->provider = $providerClass;

                $account = $provider->getAccount();

                $tokenRecord->userMapping = $account->mapping;
            }

            $tokenRecord->token = $token;
            $tokenRecord->scope = $scope;


            // save token

            if($tokenRecord->save()) {
                Craft::log(__METHOD__." : userToken Saved", LogLevel::Info, true);
            } else {
                Craft::log(__METHOD__." : Could not save userToken", LogLevel::Error);
            }
        } else {
            die('fail');
            // fail

            Craft::log(__METHOD__." : Provider process failed", LogLevel::Error);
        }

        
        // remove httpSession variables

        craft()->oauth->httpSessionClean();
        
        
        // redirect

        $this->_redirect($referer);
    }

    // --------------------------------------------------------------------

    public function actionConnectSystem()
    {

        // namespace

        $namespace = craft()->httpSession->get('oauth.namespace');

        if(!$namespace) {

            $namespace = craft()->request->getParam('namespace');

            craft()->httpSession->add('oauth.namespace', $namespace);
        }


        // get params

        $providerClass = craft()->request->getParam('provider');

        $scope = craft()->httpSession->get('oauth.scope');

        if(!$scope) {

            // scopeParam

            $scopeParam = craft()->request->getParam('scope');
            $scopeParam = unserialize(base64_decode($scopeParam));


            // scopeToken

            $scopeToken = craft()->oauth->systemTokenScope($providerClass, $namespace);


            // is scope enough ? 

            $scopeEnough = craft()->oauth->isScopeEnough($scopeParam, $scopeToken);


            // scope is not enough, connect user with new scope

            if(!$scopeEnough) {
                $scope = craft()->oauth->mixScopes($scopeParam, $scopeToken);

                craft()->httpSession->add('oauth.scope', $scope);
            }
        }
        

        // get session vars

        $namespace = craft()->httpSession->get('oauth.namespace');
        $scope = craft()->httpSession->get('oauth.scope');
        $referer = craft()->httpSession->get('oauth.referer');


        // instantiate provider

        $callbackUrl = UrlHelper::getSiteUrl(
            craft()->config->get('actionTrigger').'/oauth/public/connect',
            array('provider' => $providerClass)
        );

        $provider = craft()->oauth->instantiateProvider($providerClass, $callbackUrl, null, $scope);


        // connect provider

        $provider = craft()->oauth->connectProviderObject($provider);


        // clean httpSession
        
        craft()->oauth->httpSessionClean();


        // success : save userToken record

        if($provider) {

            $token = $provider->token();
            $token = base64_encode(serialize($token));


            // user provider default scope is none given

            if(!$scope) {
                $scope = $provider->scope;
            }


            $tokenRecord = craft()->oauth->getSystemToken($providerClass, $namespace);

            if(!$tokenRecord) {
                $tokenRecord = new Oauth_TokenRecord();
                $tokenRecord->provider = $providerClass;
                $tokenRecord->namespace = $namespace;
            }

            $tokenRecord->token = $token;
            $tokenRecord->scope = $scope;

            if($tokenRecord->save()) {
                Craft::log(__METHOD__." : userToken Saved", LogLevel::Info, true);
            } else {
                Craft::log(__METHOD__." : Could not save userToken", LogLevel::Error);
            }
        } else {
            
            // fail

            Craft::log(__METHOD__." : Provider process failed", LogLevel::Error);

            die('fail');
        }

        
        // redirect

        $this->_redirect($referer);
    }

    // --------------------------------------------------------------------

    public function actionDisconnect()
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        // get request params

        $providerClass = craft()->request->getParam('provider');
        $namespace = craft()->request->getParam('namespace');
        $userMode = (bool) craft()->request->getParam('userMode');


        // criteria conditions & params

        $criteriaConditions = '
            provider=:provider 
            ';

        $criteriaParams = array(
            ':provider' => $providerClass
            );

        if($namespace) {
            $criteriaConditions .= ' AND namespace=:namespace';
            $criteriaParams[':namespace']  = $namespace;
        }

        if($userMode) {
            $criteriaConditions .= ' AND userId=:userId';
            $criteriaParams[':userId']  = craft()->userSession->user->id;
        }


        // delete all matching records

        Oauth_TokenRecord::model()->deleteAll($criteriaConditions, $criteriaParams);


        // redirect

        $this->_redirect($_SERVER['HTTP_REFERER']);
    }

    // --------------------------------------------------------------------

    private function _redirect($referer)
    {
        Craft::log(__METHOD__." : Referer : ".$referer, LogLevel::Info, true);

        $this->redirect($referer);
    }

    // --------------------------------------------------------------------

    private function _getSessionDuration($rememberMe)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        if ($rememberMe) {
            $duration = craft()->config->get('rememberedUserSessionDuration');
        } else {
            $duration = craft()->config->get('userSessionDuration');
        }

        // Calculate how long the session should last.
        if ($duration) {
            $interval = new DateInterval($duration);
            $expire = DateTimeHelper::currentUTCDateTime();
            $currentTimeStamp = $expire->getTimestamp();
            $futureTimeStamp = $expire->add($interval)->getTimestamp();
            $seconds = $futureTimeStamp - $currentTimeStamp;
        } else {
            $seconds = 0;
        }

        return $seconds;
    }
}