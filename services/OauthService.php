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

    public function connect($namespace, $providerClass, $userToken = false, $scope = null)
    {
        if($userToken === true) {
            $userToken = 1;
        } else {
            $userToken = 0;
        }

        $params = array(
                    'namespace' => $namespace,
                    'provider' => $providerClass,
                    'userToken' => $userToken
                    );

        if($scope) {
            $params['scope'] = base64_encode(serialize($scope));
        }

        return UrlHelper::getSiteUrl(craft()->config->get('actionTrigger').'/oauth/public/authenticate', $params);
    }

    // --------------------------------------------------------------------

    public function disconnect($namespace, $providerClass)
    {
        $params = array(
                    'namespace' => $namespace,
                    'provider' => $providerClass
                    );

        return UrlHelper::getSiteUrl(craft()->config->get('actionTrigger').'/oauth/public/authenticate', $params);
    }

    // --------------------------------------------------------------------

    public function providerIsConfigured($providerClass)
    {
        $record = Oauth_ProviderRecord::model()->find('providerClass=:providerClass', array(':providerClass' => $providerClass));

        if ($record) {

            if(!empty($record->clientId) && !empty($record->clientSecret)) {
                return true;
            }
        }


        return false;
    }

    // --------------------------------------------------------------------

    public function providerIsConnected($namespace, $providerClass, $user = NULL)
    {
        $userId = craft()->userSession->user->id;

        $criteriaConditions = '
            namespace=:namespace AND
            provider=:provider AND
            userId=:userId
            ';

        $criteriaParams = array(
            ':namespace' => $namespace,
            ':userId' => $userId,
            ':provider' => $providerClass,
            );

        $tokenRecord = Oauth_TokenRecord::model()->find($criteriaConditions, $criteriaParams);

        if($tokenRecord) {
            return true;
        }

        return false;
    }

    // --------------------------------------------------------------------

    public function providerCallbackUrl($providerClass)
    {
        $params = array('provider' => $providerClass);

        return UrlHelper::getSiteUrl(craft()->config->get('actionTrigger').'/oauth/public/authenticate', $params);
    }

    // --------------------------------------------------------------------

    public function getProviderLibrary($providerClass, $namespace = null , $userToken = false)
    {
        if($namespace == null)
        {
            $class = "\\Dukt\\Connect\\$providerClass\\Provider";
            $opts = array('id' => 'x', 'secret' => 'x', 'redirect_url' => 'x');
            $provider = new $class($opts);
            return $provider;
        }

        $userId = false;

        if($userToken) {
            $user = craft()->userSession->user;

            if($user) {
                $userId = $user->id;
            }

            $criteriaConditions = '
                namespace=:namespace AND
                provider=:provider AND
                userId=:userId
                ';

            $criteriaParams = array(
                ':namespace' => $namespace,
                ':userId' => $userId,
                ':provider' => $providerClass,
                );
        } else {
            $criteriaConditions = '
                namespace=:namespace AND
                provider=:provider
                ';

            $criteriaParams = array(
                ':namespace' => $namespace,
                ':provider' => $providerClass,
                );
        }


        $tokenRecord = Oauth_TokenRecord::model()->find($criteriaConditions, $criteriaParams);

        $token = @unserialize(base64_decode($tokenRecord->token));

        if(!$token) {
            return NULL;
        }


        // Create the OAuth provider

        $providerClass = $tokenRecord->provider;

        $providerRecord = Oauth_ProviderRecord::model()->find('providerClass=:providerClass', array(':providerClass' => $providerClass));

        $opts = array(
            'id' => $providerRecord->clientId,
            'secret' => $providerRecord->clientSecret,
            'redirect_url' => \Craft\UrlHelper::getActionUrl('oauth/public/authenticate/', array('provider' => $providerClass))
        );

        $class = "\\Dukt\\Connect\\$providerClass\\Provider";
        $provider = new $class($opts);
        $provider->setToken($token);
        // var_dump($provider);
        // die();

        return $provider;
    }

    // --------------------------------------------------------------------

    public function getAccount($namespace, $providerClass)
    {
        $provider = $this->getProviderLibrary($providerClass, $namespace);

        if(!$provider) {
            return NULL;
        }

        $account = @$provider->getAccount();

        if(!$account) {
            return NULL;
        }

        return $account;
    }

    // --------------------------------------------------------------------

    public function enable($id)
    {
        $record = $this->getServiceById($id);
        $record->enabled = true;
        $record->save();

        return true;
    }

    // --------------------------------------------------------------------

    public function disable($id)
    {
        $record = $this->getServiceById($id);
        $record->enabled = false;
        $record->save();

        return true;
    }

    // --------------------------------------------------------------------

    public function getProvider($providerClass)
    {

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
        $record = $this->serviceRecord->findByPk($id);

        if ($record) {

            return $record;
        }

        return new Oauth_ServiceModel();
    }

    // --------------------------------------------------------------------

    public function saveService(Oauth_ServiceModel &$model)
    {
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
        $model = new Oauth_ServiceModel();

        $model->setAttributes($attributes);

        return $model;
    }

    // --------------------------------------------------------------------

    public function connectService($record = false)
    {
        if(!$record)
        {
            $serviceId = craft()->request->getParam('id');

            $record = $this->serviceRecord->findByPk($serviceId);
        }


        $className = $record->className;

        $provider = \OAuth\OAuth::provider($className, array(
            'id' => $record->clientId,
            'secret' => $record->clientSecret,
            'redirect_url' => \Craft\UrlHelper::getActionUrl('campaigns/settings/serviceCallback/', array('id' => $record->id))
        ));

        $provider = $provider->process(function($url, $token = null) {

            if ($token) {
                $_SESSION['token'] = base64_encode(serialize($token));
            }

            header("Location: {$url}");

            exit;

        }, function() {
            return unserialize(base64_decode($_SESSION['token']));
        });


        $token = $provider->token();

        $record->token = base64_encode(serialize($token));

        $record->save();


        craft()->request->redirect(UrlHelper::getUrl('campaigns/settings'));

    }

    // --------------------------------------------------------------------

    public function service($id)
    {

        $service = $this->serviceRecord->findByPk($id);


        $providerParams = array();
        $providerParams['id'] = $service->clientId;
        $providerParams['secret'] = $service->clientSecret;
        $providerParams['redirect_url'] = "http://google.fr";

        try {
            $provider = \OAuth\OAuth::provider($service->providerClass, $providerParams);

            $token = unserialize(base64_decode($service->token));

            // refresh token if needed ?

            if(!$token)
            {
                throw new \Exception('Invalid Token');
            }

            $provider->setToken($token);

        } catch(\Exception $e)
        {
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
        $service = $this->serviceRecord->findByPk($serviceId);


        $providerParams = array();
        $providerParams['id'] = $service->clientId;
        $providerParams['secret'] = $service->clientSecret;
        $providerParams['redirect_url'] = "http://google.fr";

        try {
            $provider = \OAuth\OAuth::provider($service->className, $providerParams);

            $token = unserialize(base64_decode($service->token));

            // refresh token if needed ?

            if(!$token)
            {
                throw new \Exception('Invalid Token');
            }

            $provider->setToken($token);

        } catch(\Exception $e)
        {
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

    public function getProviders()
    {
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
        $records = $this->serviceRecord->findAll(array('order'=>'t.title'));

        return Campaigns_ServiceModel::populateModels($records, 'id');
    }

    // --------------------------------------------------------------------

    public function deleteServiceById($id)
    {
        return $this->serviceRecord->deleteByPk($id);
    }

    // --------------------------------------------------------------------

    public function resetServiceToken($providerClass)
    {
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

