<?php

namespace Craft;

require_once(CRAFT_PLUGINS_PATH.'campaigns/vendor/autoload.php');

use ReflectionClass;
use Symfony\Component\Finder\Finder;

class OauthService extends BaseApplicationComponent
{
    protected $serviceRecord;

    public function __construct($serviceRecord = null)
    {
        $this->serviceRecord = $serviceRecord;
        if (is_null($this->serviceRecord)) {
            $this->serviceRecord = Oauth_ServiceRecord::model();
        }
    }

    // --------------------------------------------------------------------

    public function run($namespace, $providerClass, $url) {
        $provider = $this->getProvider($namespace, $providerClass);

        $url = $url.'?alt=json&'.http_build_query(array(
            'access_token' => $provider->token->access_token,
        ));
        // echo $url;
        // die();
        $response = json_decode(file_get_contents($url), true);

        return $response;
    }

    private function apiCall($url, $params = array(), $method='get')
    {
        $developerKey = $this->getDeveloperKey();

        if(is_array($params))
        {
            $params['access_token'] = $this->provider->token->access_token;
            $params['key'] = $developerKey;
            $params['v'] = 2;
        }

        $url = 'https://gdata.youtube.com/feeds/api/'.$url;

        if($method=="get")
        {
            $url .= '?'.http_build_query($params);
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Authorization:Bearer '.$this->provider->token->access_token,
                'Content-Type:application/atom+xml',
                'X-GData-Key:key='.$developerKey
            ));

        if($method=="post")
        {
            curl_setopt ($curl, CURLOPT_POST, true);
            curl_setopt ($curl, CURLOPT_POSTFIELDS, $params);
        }

        if($method=='delete')
        {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $result = curl_exec($curl);
        $curlInfo = curl_getinfo($curl);
        curl_close ($curl);


        if($curlInfo['http_code'] == 401 && strpos($result, "Token invalid") !== false)
        {
            // refresh token
            // $providerParams = array('grant_type' => 'refresh_token');
            // $code = $provider
            // $this->provider->access($code, $providerParams);
            // var_dump($this->provider);
            throw new \Exception('Provider Invalid Token');
        }

        if($method != 'delete')
        {
            $xml_obj = simplexml_load_string($result);

            if(isset($xml_obj->error))
            {
                throw new \Exception($xml_obj->error->internalReason);
            }

            return $xml_obj;
        }

        return true;
    }

    // --------------------------------------------------------------------

    public function isAuthenticated($namespace, $providerClass, $user = NULL)
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

    public function authenticate($namespace, $providerClass, $scope) {
        // {{ actionUrl('oauth/public/authenticate', {provider:provider, namespace:'connect.user'}) }}
        return UrlHelper::getActionUrl('oauth/public/authenticate', array(
                    'namespace' => $namespace,
                    'provider' => $providerClass,
                    'scope' => base64_encode(serialize($scope))
                    ));
    }

    // --------------------------------------------------------------------

    public function deauthenticate($namespace, $providerClass)
    {
        return UrlHelper::getActionUrl('oauth/public/deauthenticate', array(
                    'namespace' => $namespace,
                    'provider' => $providerClass
                    ));
    }

    // --------------------------------------------------------------------

    public function getProvider($namespace, $providerClass)
    {
        $user = craft()->userSession->user;
        $userId = false;

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

        $tokenRecord = Oauth_TokenRecord::model()->find($criteriaConditions, $criteriaParams);

        $token = @unserialize(base64_decode($tokenRecord->token));

        if(!$token) {
            return NULL;
        }


        // Create the OAuth provider

        $providerClass = $tokenRecord->provider;

        $providerRecord = Oauth_ServiceRecord::model()->find('providerClass=:providerClass', array(':providerClass' => $providerClass));

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


    public function getAccount($namespace, $providerClass)
    {
        $provider = $this->getProvider($namespace, $providerClass);

        if(!$provider) {
            return NULL;
        }

        $account = $provider->getAccount();

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

    public function outputToken($providerClass)
    {
        //$provider = $this->getServiceByProviderClass($providerClass);

        $token = craft()->httpSession->get('connectToken.'.$providerClass);
        $token = base64_decode($token);
        $token = unserialize($token);
        return $token;

        $service = $this->service($provider->id);

        return $service->getUserInfo();
    }

    // --------------------------------------------------------------------

    public function getServiceByProviderClass($providerClass)
    {

        // get the option

        $record = Oauth_ServiceRecord::model()->find('providerClass=:providerClass', array(':providerClass' => $providerClass));

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

        if (null === ($record = Oauth_ServiceRecord::model()->find('providerClass=:providerClass', array(':providerClass' => $class)))) {
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

    public function getTokens() {
        $tokens = Oauth_TokenRecord::model()->findAll();
        //array('order'=>'t.title')
        return $tokens;
    }

    // --------------------------------------------------------------------

    public function getProviders()
    {
        $directory = CRAFT_PLUGINS_PATH.'connect/libraries/Dukt/Connect/';

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

        $record = Oauth_ServiceRecord::model()->find('providerClass=:providerClass', array(':providerClass' => $providerClass));

        if($record)
        {
            $record->token = NULL;
            return $record->save();
        }

        return false;
    }

}

