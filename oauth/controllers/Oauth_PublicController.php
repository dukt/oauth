<?php

namespace Craft;

require(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');

class Oauth_PublicController extends BaseController
{
    protected $allowAnonymous = true;

    // --------------------------------------------------------------------

    public function actionConnect()
    {
        $namespace = craft()->oauth->sessionAdd('oauth.namespace', craft()->request->getParam('namespace'));


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

        $providerHandle = craft()->request->getParam('provider');
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

        $conditions = 'provider=:provider';
        $params = array(':provider' => $providerHandle);

        if($namespace) {
            $conditions .= ' AND namespace=:namespace';
            $params[':namespace']  = $namespace;
        }

        if($userMode) {
            $conditions .= ' AND userId=:userId';
            $params[':userId']  = craft()->userSession->user->id;
        }


        // delete all matching records

        Oauth_TokenRecord::model()->deleteAll($conditions, $params);


        // redirect

        $this->_redirect($redirect);
    }

    // --------------------------------------------------------------------

    private function _connectUser()
    {
        // session variables

        $providerHandle  = craft()->httpSession->get('oauth.providerClass');
        $social         = craft()->httpSession->get('oauth.social');
        $socialCallback = craft()->httpSession->get('oauth.socialCallback');
        $referer        = craft()->httpSession->get('oauth.referer');
        $scope          = craft()->httpSession->get('oauth.scope');

        $token = craft()->oauth->getToken($providerHandle);

        // scope

        if(!$scope) {

            // tokenScope

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

        $provider = craft()->oauth->getProvider($providerHandle);

        if(!$provider) {
            craft()->userSession->setError(Craft::t("Provider not configured."));
            $this->redirect($referer);
            return;
        }

        $provider->setScope($scope);


        // post-connect

        if($provider) {


            // ----------------------
            // social bypass
            // ----------------------

            if($social) {
                craft()->httpSession->add('oauth.token', base64_encode(serialize($provider->getToken())));

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
                $scope = $provider->getScope();
            }

            $account = $provider->getAccount();


            // save token

            $token = craft()->oauth->getToken($providerHandle);

            $token->userId = craft()->userSession->user->id;
            $token->provider = $providerHandle;
            $token->userMapping = $account['uid'];
            $token->token = base64_encode(serialize($provider->getToken()));
            $token->scope = $scope;

            craft()->oauth->tokenSave($token);

        } else {
            die('fail');
        }


        // remove httpSession variables

        craft()->oauth->sessionClean();


        // redirect

        $this->_redirect($referer);
    }

    // --------------------------------------------------------------------

    private function _connectSystem()
    {
        // namespace

        $namespace = craft()->oauth->sessionAdd('oauth.namespace', craft()->request->getParam('namespace'));


        // session vars

        $providerHandle = craft()->httpSession->get('oauth.providerClass');
        $scope         = craft()->httpSession->get('oauth.scope');
        $referer       = craft()->httpSession->get('oauth.referer');


        // connect provider

        $provider = craft()->oauth->getProvider($providerHandle);

        $provider->setScope($scope);


        // save token

        if($provider) {

            // remove any existing token with this namespace

            craft()->oauth->tokenDeleteByNamespace($providerHandle, $namespace);


            // token

            $token = $provider->getToken();
            $token = base64_encode(serialize($token));


            // scope

            if(!$scope) {
                $scope = $provider->getScope();
            }


            // save token

            $model = new Oauth_TokenModel();
            $model->provider = $providerHandle;
            $model->namespace = $namespace;
            $model->token = $token;
            $model->scope = $scope;

            craft()->oauth->tokenSave($model);


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