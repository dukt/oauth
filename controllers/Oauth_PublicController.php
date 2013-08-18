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


        // session var : scope

        $_wantedScope = craft()->request->getParam('scope');
        $_wantedScope = unserialize(base64_decode($_wantedScope));

        $scope = craft()->oauth->httpSessionAdd('oauth.scope', $_wantedScope);


        // connect user or system

        if(craft()->httpSession->get('oauth.userMode')) {
            $this->_connectUser();
        } else {
            $this->_connectSystem();
        }
    }

    // --------------------------------------------------------------------

    public function actionDisconnect()
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        // get request params

        $providerClass = craft()->request->getParam('provider');

        $namespace = craft()->request->getParam('namespace');

        if($namespace) {
            $userMode = false;
        } else {
            $userMode = true;
        }
        

        // criteria conditions & params

        $criteriaConditions = 'provider=:provider';
        $criteriaParams = array(':provider' => $providerClass);

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

    private function _connectUser()
    {
        // session variables

        $providerClass  = craft()->httpSession->get('oauth.providerClass');
        $social         = craft()->httpSession->get('oauth.social');
        $socialCallback = craft()->httpSession->get('oauth.socialCallback');
        $referer        = craft()->httpSession->get('oauth.referer');
        $scope          = craft()->httpSession->get('oauth.scope');


        // scope

        if(!$scope) {

            // tokenScope

            $tokenScope = craft()->oauth->tokenScopeByCurrentUser($providerClass);


            // is scope enough ? 

            $scopeEnough = craft()->oauth->scopeIsEnough($scope, $tokenScope);


            // scope is not enough, connect user with new scope

            if(!$scopeEnough) {
                $scope = craft()->oauth->scopeMix($scope, $tokenScope);

                craft()->httpSession->add('oauth.scope', $scope);
            }
        }
        

        // instantiate provider

        $callbackUrl = UrlHelper::getSiteUrl(
            craft()->config->get('actionTrigger').'/oauth/public/connect',
            array('provider' => $providerClass)
        );

        $provider = craft()->oauth->providerInstantiate($providerClass, $callbackUrl, null, $scope);


        // connect provider

        $provider = craft()->oauth->providerConnect($provider);


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

            // craft user must be logged in

            if(!craft()->userSession->user) {
                return null;
            }


            // set default scope if none set

            if(!$scope) {
                $scope = $provider->scope;
            }


            // get existing token record

            $tokenRecord = craft()->oauth->tokenRecordByCurrentUser($providerClass);


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

            $tokenRecord->save();
        } else {
            die('fail');
        }

        
        // remove httpSession variables

        craft()->oauth->httpSessionClean();
        
        
        // redirect

        $this->_redirect($referer);
    }

    // --------------------------------------------------------------------

    private function _connectSystem()
    {
        // namespace

        $namespace = craft()->oauth->httpSessionAdd('oauth.namespace', craft()->request->getParam('namespace'));


        // session vars

        $providerClass = craft()->httpSession->get('oauth.providerClass');
        $scope         = craft()->httpSession->get('oauth.scope');
        $referer       = craft()->httpSession->get('oauth.referer');


        // connect provider

        $callbackUrl = UrlHelper::getSiteUrl(
            craft()->config->get('actionTrigger').'/oauth/public/connect',
            array('provider' => $providerClass)
        );

        $provider = craft()->oauth->providerInstantiate($providerClass, $callbackUrl, null, $scope);
        $provider = craft()->oauth->providerConnect($provider);


        // clean httpSession
        
        craft()->oauth->httpSessionClean();


        // save token

        if($provider) {

            // remove any existing token with this namespace

            $tokenRecord = craft()->oauth->tokenRecordByNamespace($providerClass, $namespace);

            if($tokenRecord) {
                $tokenRecord->delete();
            }


            // token

            $token = $provider->token();
            $token = base64_encode(serialize($token));


            // scope

            if(!$scope) {
                $scope = $provider->scope;
            }

            
            // save token record

            $tokenRecord = new Oauth_TokenRecord();
            $tokenRecord->provider = $providerClass;
            $tokenRecord->namespace = $namespace;
            $tokenRecord->token = $token;
            $tokenRecord->scope = $scope;

            $tokenRecord->save();

        } else {
            die('fail');
        }

        
        // redirect

        $this->_redirect($referer);
    }

    // --------------------------------------------------------------------

    private function _redirect($referer)
    {
        Craft::log(__METHOD__." : Referer : ".$referer, LogLevel::Info, true);

        $this->redirect($referer);
    }

    // --------------------------------------------------------------------
}