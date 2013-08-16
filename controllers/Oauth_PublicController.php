<?php

namespace Craft;

require(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');

class Oauth_PublicController extends BaseController
{
    protected $allowAnonymous = true;

    // --------------------------------------------------------------------

    public function actionConnect()
    {
        // userMode

        $userMode = craft()->httpSession->get('oauth.userMode');

        if(!$userMode) {

            $userMode = (bool) craft()->request->getParam('userMode');

            craft()->httpSession->add('oauth.userMode', $userMode);
        }


        // referer

        if(!craft()->httpSession->get('oauth.referer')) {
            craft()->httpSession->add('oauth.referer', $_SERVER['HTTP_REFERER']);
        }


        // connect user or system

        if($userMode) {
            // die('user');
            $this->actionConnectUser();
        } else {
            // die('not user');
            $this->actionConnectSystem();
        }
    }

    // --------------------------------------------------------------------

    public function actionConnectUser()
    {
        // get params

        $providerClass = craft()->request->getParam('provider');

        $scope = craft()->httpSession->get('oauth.scope');

        var_dump($scope);


        if(!$scope) {

            

            // scopeParam

            $scopeParam = craft()->request->getParam('scope');
            $scopeParam = unserialize(base64_decode($scopeParam));


            // scopeToken

            $scopeToken = $this->userTokenScope($providerClass);


            // is scope enough ? 

            $scopeEnough = craft()->oauth->isScopeEnough($scopeParam, $scopeToken);


            // scope is not enough, connect user with new scope

            if(!$scopeEnough) {
                $scope = craft()->oauth->mixScopes($scopeParam, $scopeToken);

                craft()->httpSession->add('oauth.scope', $scope);
            }
        }
        
        $this->_actionConnectUser($providerClass);
    }

    // --------------------------------------------------------------------

    private function _actionConnectUser($providerClass, $scope = null)
    {
        // initProvider

        $provider = $this->initProvider($providerClass, $scope);


        //referer

        $referer = craft()->httpSession->get('oauth.referer');


        //scope

        $scope = craft()->httpSession->get('oauth.scope');
        //$scope = unserialize(base64_decode($scope));

        
        // remove httpSession variables

        craft()->httpSession->remove('oauth.userMode');
        craft()->httpSession->remove('oauth.referer');
        craft()->httpSession->remove('oauth.scope');
        
        if($provider) {

            // success : save userToken record

            $token = $provider->token();
            $token = base64_encode(serialize($token));

            // var_dump($scope);
            // die();
            if(!$scope) {
                $scope = $provider->scope;
            }


            $tokenRecord = craft()->oauth->getUserToken($providerClass);

            if(!$tokenRecord) {
                $tokenRecord = new Oauth_TokenRecord();
                $tokenRecord->userId = craft()->userSession->user->id;
                $tokenRecord->provider = $providerClass;
            }

            $tokenRecord->token = $token;
            $tokenRecord->scope = $scope;

            // var_dump($tokenRecord->userId, $tokenRecord->provider, $tokenRecord->token, $tokenRecord->scope);

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

            $scopeToken = $this->systemTokenScope($providerClass, $namespace);


            // is scope enough ? 

            $scopeEnough = craft()->oauth->isScopeEnough($scopeParam, $scopeToken);


            // scope is not enough, connect user with new scope

            if(!$scopeEnough) {
                $scope = craft()->oauth->mixScopes($scopeParam, $scopeToken);

                craft()->httpSession->add('oauth.scope', $scope);
            }
        }
        
        $this->_actionConnectSystem($providerClass);
    }

    // --------------------------------------------------------------------

    private function _actionConnectSystem($providerClass)
    {
        // get session vars

        $namespace = craft()->httpSession->get('oauth.namespace');
        $scope = craft()->httpSession->get('oauth.scope');
        $referer = craft()->httpSession->get('oauth.referer');


        // initProvider

        $provider = $this->initProvider($providerClass, $scope);


        // remove httpSession variables

        craft()->httpSession->remove('oauth.userMode');
        craft()->httpSession->remove('oauth.referer');
        craft()->httpSession->remove('oauth.scope');
        craft()->httpSession->remove('oauth.namespace');
        
        if($provider) {

            // success : save userToken record

            $token = $provider->token();
            $token = base64_encode(serialize($token));


            // user provider default scope is none given

            if(!$scope) {
                $scope = $provider->scope;
            }


            $tokenRecord = craft()->oauth->getSystemToken($providerClass, $namespace);

            // var_dump($tokenRecord, $providerClass, $namespace);

            // die();

            if(!$tokenRecord) {
                $tokenRecord = new Oauth_TokenRecord();
                $tokenRecord->provider = $providerClass;
                $tokenRecord->namespace = $namespace;
            }

            $tokenRecord->token = $token;
            $tokenRecord->scope = $scope;

            // var_dump($tokenRecord->userId, $tokenRecord->provider, $tokenRecord->token, $tokenRecord->scope);

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
    // --------------------------------------------------------------------
    // --------------------------------------------------------------------

    private function initProvider($providerClass, $scope = null)
    {
        // providerRecord

        $providerRecord = $this->providerRecord($providerClass);


        // callbackUrl

        $params = array('provider' => $providerClass);

        $callbackUrl = UrlHelper::getSiteUrl(craft()->config->get('actionTrigger').'/oauth/public/connect', $params);


        // define provider options (id, secret, redirect_url, scope)

        $opts = array(
            'id' => $providerRecord->clientId,
            'secret' => $providerRecord->clientSecret,
            'redirect_url' => $callbackUrl
        );

        if(is_array($scope)) {
            if(count($scope) > 0) {
                $opts['scope'] = $scope;
            }
        }

        $class = "\\Dukt\\Connect\\$providerRecord->providerClass\\Provider";


        // instantiate provider object

        $provider = new $class($opts);


        // connect provider

        try {
            Craft::log(__METHOD__." : Provider processing", LogLevel::Info, true);

            $provider = $provider->process(function($url, $token = null) {

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

            $this->_redirect(craft()->httpSession->get('oauthReferer'));
        }

        return $provider;
    }

    // --------------------------------------------------------------------

    private function providerRecord($providerClass)
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

    private function userTokenRecord($providerClass)
    {
        $tokenRecord = Oauth_TokenRecord::model()->find(

            // conditions

            'provider=:provider AND userId=:userId',

            
            // params

            array(
                ':provider' => $providerClass,
                ':userId' => craft()->userSession->user->id,
            )
        );

        if($tokenRecord) {
            return $tokenRecord;
        }

        return null;
    }

    // --------------------------------------------------------------------

    private function systemTokenRecord($providerClass, $namespace)
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

    private function userTokenScope($providerClass)
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

    private function systemTokenScope($providerClass, $namespace)
    {
        // provider record

        $providerRecord = $this->providerRecord($providerClass);

        if($providerRecord) {

            // user token record

            $tokenRecord = $this->systemTokenRecord($providerClass, $namespace);

            if($tokenRecord) {

                $tokenScope = @unserialize(base64_decode($tokenRecord->scope));

                return $tokenScope;
            }
        }

        return null;
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