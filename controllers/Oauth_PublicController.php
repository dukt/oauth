<?php

namespace Craft;

require(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');

class Oauth_PublicController extends BaseController
{
    protected $allowAnonymous = true;

    // --------------------------------------------------------------------

    public function actionAuthenticate()
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $className = craft()->request->getParam('provider');
        $namespace = craft()->request->getParam('namespace');

        $userToken = craft()->httpSession->get('oauthUserToken');

        if(!$userToken) {
            $userToken = (bool) craft()->request->getParam('userToken');
            craft()->httpSession->add('oauthUserToken', $userToken);
        }

        $scope = craft()->request->getParam('scope');
        $scope = @unserialize(base64_decode($scope));

        $referer = craft()->httpSession->get('oauthReferer');

        if(!$referer && isset($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
            craft()->httpSession->add('oauthReferer', $referer);
        }

        if($namespace) {
            craft()->httpSession->add('oauthNamespace', $namespace);
        }

        $serviceRecord = Oauth_ProviderRecord::model()->find('providerClass=:providerClass', array(':providerClass' => $className));

        $className = $serviceRecord->providerClass;




        //$provider = \OAuth\OAuth::provider($className, );

        $opts = array(
            'id' => $serviceRecord->clientId,
            'secret' => $serviceRecord->clientSecret,
            'redirect_url' => craft()->oauth->providerCallbackUrl($className),
        );

        if($scope) {
            $opts['scope'] = $scope;
        }

        $class = "\\Dukt\\Connect\\$className\\Provider";


        $provider = new $class($opts);

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

            Craft::log(__METHOD__." : Provider process failed : ".$e->getMessage(), LogLevel::Info, true);
            Craft::log(__METHOD__." : Referer : ".$referer, LogLevel::Info, true);
            $this->redirect($referer);
        }


        $namespace = craft()->httpSession->get('oauthNamespace');

        craft()->httpSession->remove('oauthNamespace');


        $token = $provider->token();

        $token = base64_encode(serialize($token));

        craft()->httpSession->add('oauthToken.'.$className, $token);



        // oauth the user

        $tokenArray = array();

        $tokenArray['namespace'] = $namespace;
        $tokenArray['provider'] = $className;

        $tokenArray['token'] = $token;

        $userToken = craft()->httpSession->get('oauthUserToken');
        craft()->httpSession->remove('oauthUserToken');

        if($userToken === true) {


            Craft::log(__METHOD__." : User Token", LogLevel::Info, true);
            //die('3');
            try {
                $account = $provider->getAccount();
            } catch (\Exception $e) {

                $referer = craft()->httpSession->get('oauthReferer');
                craft()->httpSession->remove('oauthReferer');

                // var_dump($referer);
                // die();

                Craft::log(__METHOD__." : Could not get account, so we redirect.", LogLevel::Info, true);
                Craft::log(__METHOD__." : Redirect : ".$referer, LogLevel::Info, true);

                $this->redirect($referer);

            }
            var_dump($account);

            if(isset($account->mapping)) {
                $tokenArray['userMapping'] = $account->mapping;
            }

            //die('4');

            $user = null;
            $userId =  craft()->userSession->id;

            // use the current user if possible

            if($userId) {
                $user = craft()->users->getUserById($userId);
            }

            // otherwise check if we have a matching email

            if(!$user) {
                $user = craft()->users->getUserByUsernameOrEmail($account->email);
            }


            // check with mapping

            if(!$user && isset($account->mapping)) {

                $criteriaConditions = '
                    namespace=:namespace AND
                    provider=:provider AND
                    userMapping=:userMapping
                    ';

                $criteriaParams = array(
                    ':namespace' => $tokenArray['namespace'],
                    ':provider' => $tokenArray['provider'],
                    ':userMapping' => $tokenArray['userMapping'],
                    );


                $tokenRecord = Oauth_TokenRecord::model()->find($criteriaConditions, $criteriaParams);

                if($tokenRecord) {
                    $userId = $tokenRecord->userId;
                    $user = craft()->users->getUserById($userId);
                }
            }

            // the account email doesn't match any user, create one

            if(!$user) {
                //die('1');

                $newUser = new UserModel();
                $newUser->username = $account->email;
                $newUser->email = $account->email;

                craft()->users->saveUser($newUser);

                $user = craft()->users->getUserByUsernameOrEmail($account->email);
            }

            
            //die('2');
            $tokenArray['userId'] = $user->id;

            $criteriaConditions = '
                namespace=:namespace AND
                provider=:provider AND
                userId=:userId
                ';

            $criteriaParams = array(
                ':namespace' => $tokenArray['namespace'],
                ':provider' => $tokenArray['provider'],
                ':userId' => $tokenArray['userId'],
                );
        } else {

            Craft::log(__METHOD__." : System Token", LogLevel::Info, true);

            $criteriaConditions = '
                namespace=:namespace AND
                provider=:provider
                ';

            $criteriaParams = array(
                ':namespace' => $tokenArray['namespace'],
                ':provider' => $tokenArray['provider']
                );
        }


        $tokenRecord = Oauth_TokenRecord::model()->find($criteriaConditions, $criteriaParams);

        if(!$tokenRecord) {
            $tokenRecord = new Oauth_TokenRecord();
        }

        if(isset($tokenArray['userMapping'])) {
            $tokenRecord->userMapping = $tokenArray['userMapping'];
        }
        
        $tokenRecord->namespace = $tokenArray['namespace'];
        $tokenRecord->provider = $tokenArray['provider'];
        $tokenRecord->token = $tokenArray['token'];

        if($userToken) {
            $tokenRecord->userId = $tokenArray['userId'];
        }

        if(!$tokenRecord->save()) {
            Craft::log(__METHOD__." : Could not save token", LogLevel::Info, true);
        }

        if($userToken && isset(craft()->social_userSession)) {
            craft()->social_userSession->login($token);
        }

        $referer = craft()->httpSession->get('oauthReferer');
        craft()->httpSession->remove('oauthReferer');

        // var_dump($referer);
        // die();

        Craft::log(__METHOD__." : Redirect : ".$referer, LogLevel::Info, true);

        $this->redirect($referer);

        //$this->redirect($finalRedirect);
    }

    // --------------------------------------------------------------------

    public function actionDeauthenticate()
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $providerClass = craft()->request->getParam('provider');
        $namespace = craft()->request->getParam('namespace');

        // $user = craft()->userSession->user;
        // $userId = false;

        // if($user) {
        //     $userId = $user->id;
        // }


        $criteriaConditions = '
            namespace=:namespace AND
            provider=:provider
            ';

        $criteriaParams = array(
            ':namespace' => $namespace,
            ':provider' => $providerClass,
            );

        $tokenRecord = Oauth_TokenRecord::model()->find($criteriaConditions, $criteriaParams);

        $tokenRecord->delete();

        Craft::log(__METHOD__." : Redirect : ".$_SERVER['HTTP_REFERER'], LogLevel::Info, true);

        $this->redirect($_SERVER['HTTP_REFERER']);
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