<?php

namespace Craft;

require(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');

class Oauth_PublicController extends BaseController
{
    protected $allowAnonymous = true;

    // --------------------------------------------------------------------

    public function actionConnect()
    {
        $namespace = craft()->oauth->httpSessionAdd('oauth.namespace', craft()->request->getParam('namespace'));


        // connect user or system

        if($namespace) {

            $this->_connectSystem();
        } else {
            $this->_connectUser();
        }
    }

    // --------------------------------------------------------------------

    public function actionDisconnect()
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        // redirect url

        $redirect = craft()->httpSession->get('oauth.referer');

        if(!$redirect) {
            $redirect = $_SERVER['HTTP_REFERER'];
        }


        // get request params

        $providerClass = craft()->request->getParam('provider');
        $namespace = craft()->request->getParam('namespace');



        if($namespace) {
            $userMode = false;
        } else {
            if(craft()->userSession->user) {
                $userMode = true;
            } else {
                $this->_redirect($redirect);
            }
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

        $this->_redirect($redirect);
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

            $token = craft()->oauth->getToken($providerClass);

            $tokenScope = @unserialize(base64_decode($token->scope));


            // is scope enough ?

            $scopeEnough = craft()->oauth->scopeIsEnough($scope, $tokenScope);


            // scope is not enough, connect user with new scope

            if(!$scopeEnough) {
                $scope = craft()->oauth->scopeMix($scope, $tokenScope);

                craft()->httpSession->add('oauth.scope', $scope);
            }
        }


        // instantiate provider

        $providerSource = craft()->oauth->getProviderSource($providerClass);

        $providerSource->connect(null, $scope);


        // post-connect

        if($providerSource) {

            // real token

            $realToken = $providerSource->getToken();
            $realToken = base64_encode(serialize($realToken));


            // ----------------------
            // social bypass
            // ----------------------

            if($social) {
                craft()->httpSession->add('oauth.token', $realToken);

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
                $scope = $providerSource->getScope('scope');
            }

            $account = $providerSource->getAccount();


            // save token

            $token = craft()->oauth->getToken($providerClass);

            $token->userId = craft()->userSession->user->id;
            $token->provider = $providerClass;
            $token->userMapping = $account['uid'];
            $token->token = $realToken;
            $token->scope = $scope;

            craft()->oauth->tokenSave($token);

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

        $providerSource = craft()->oauth->getProviderSource($providerClass);

        $providerSource->connect(null, $scope);


        // save token

        if($providerSource) {

            // remove any existing token with this namespace

            craft()->oauth->tokenDeleteByNamespace($providerClass, $namespace);


            // token

            $token = $providerSource->getToken();
            $token = base64_encode(serialize($token));


            // scope

            if(!$scope) {
                $scope = $providerSource->scope;
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