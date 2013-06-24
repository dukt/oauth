<?php

namespace Craft;

require(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');

class Oauth_PublicController extends BaseController
{
    protected $allowAnonymous = true;

    // --------------------------------------------------------------------

    public function actionAuthenticate()
    {
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

        $provider = $provider->process(function($url, $token = null) {

            if ($token) {
                $_SESSION['token'] = base64_encode(serialize($token));
            }

            header("Location: {$url}");

            exit;

        }, function() {
            return unserialize(base64_decode($_SESSION['token']));
        });


        $namespace = craft()->httpSession->get('oauthNamespace');

        craft()->httpSession->remove('oauthNamespace');


        $token = $provider->token();

        $token = base64_encode(serialize($token));

        craft()->httpSession->add('oauthToken.'.$className, $token);


        // $service = new {}($provider);



        // save token


        // $serviceRecord->token = $token;

        $account = $provider->getAccount();

        $user = craft()->users->getUserByUsernameOrEmail($account->email);


        if(!$user) {
            // the account email doesn't match any user, create one

            $newUser = new UserModel();
            $newUser->username = $account->email;
            $newUser->email = $account->email;

            craft()->users->saveUser($newUser);

            $user = craft()->users->getUserByUsernameOrEmail($account->email);
        }


        // oauth the user

        $tokenArray = array();
        $tokenArray['namespace'] = $namespace;
        $tokenArray['provider'] = $className;

        $tokenArray['token'] = $token;


        $userToken = craft()->httpSession->get('oauthUserToken');
        craft()->httpSession->remove('oauthUserToken');

        if($userToken === true) {
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

        $tokenRecord->namespace = $tokenArray['namespace'];
        $tokenRecord->provider = $tokenArray['provider'];
        $tokenRecord->token = $tokenArray['token'];

        if($userToken) {
            $tokenRecord->userId = $tokenArray['userId'];
        }

        $tokenRecord->save();

        if($userToken && isset(craft()->connect_userSession)) {
            craft()->connect_userSession->login($token);
        }

        $referer = craft()->httpSession->get('oauthReferer');
        craft()->httpSession->remove('oauthReferer');

        // var_dump($referer);
        // die();

        $this->redirect($referer);

        //$this->redirect($finalRedirect);
    }

    // --------------------------------------------------------------------

    public function actionDeauthenticate()
    {
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

        $this->redirect($_SERVER['HTTP_REFERER']);
    }

    // --------------------------------------------------------------------

    private function _getSessionDuration($rememberMe)
    {
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