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
            $this->actionConnectUser();
        } else {
            $this->actionConnectSystem();
        }
    }

    // --------------------------------------------------------------------

    public function actionConnectUser()
    {
        // get params

        $providerClass = craft()->request->getParam('provider');

        $scopeParam = craft()->request->getParam('scope');
        $scopeParam = @unserialize(base64_decode($scopeParam));


        // scopeToken

        $scopeToken = $this->tokenScope($providerClass);


        // is scope enough ? 

        $scopeEnough = $this->isScopeEnough($scopeParam, $scopeToken);


        // scope is not enough, connect user with new scope

        if(!$scopeEnough) {
            $scope = $this->mixScopes($scopeParam, $scopeToken);

            $this->_actionConnectUser($providerClass, $scope);
        }
    }

    // --------------------------------------------------------------------

    private function _actionConnectUser($providerClass, $scope = null)
    {
        // initProvider

        $provider = $this->initProvider($providerClass, $scope);

        //referer

        $referer = craft()->httpSession->get('oauth.referer');

        
        // remove httpSession variables

        craft()->httpSession->remove('oauth.userMode');
        craft()->httpSession->remove('oauth.referer');
        
        if($provider) {

            // success : save userToken record

            $token = $provider->token();

            $token = base64_encode(serialize($token));

            $tokenRecord = craft()->oauth->getUserToken($providerClass);

            if(!$tokenRecord) {
                $tokenRecord = new Oauth_TokenRecord();
                $tokenRecord->userId = craft()->userSession->user->id;
                $tokenRecord->provider = $providerClass;
            }

            $tokenRecord->token = $token;

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
        // remove systemToken records for this namespace and provider

        // connect in order to create a new systemToken record for this namespace and provider
    }

    // --------------------------------------------------------------------

    public function actionDisconnect()
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        // get request params

        $providerClass = craft()->request->getParam('provider');
        $namespace = craft()->request->getParam('namespace');


        // criteria conditions & params

        $criteriaConditions = '
            provider=:provider 
            AND userId is not null
            ';

        $criteriaParams = array(
            ':provider' => $providerClass
            );

        if($namespace) {
            $criteriaConditions .= ' AND namespace=:namespace';
            $criteriaParams[':namespace']  = $namespace;
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

    private function isScopeEnough($scope1, $scope2)
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

    private function mixScopes($scope1, $scope2)
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

    private function tokenScope($providerClass)
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