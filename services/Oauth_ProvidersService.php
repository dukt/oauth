<?php

namespace Craft;

require_once(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');

use ReflectionClass;
use Symfony\Component\Finder\Finder;

class Oauth_ProvidersService extends BaseApplicationComponent
{
    // --------------------------------------------------------------------

    protected $providerRecord;

    // --------------------------------------------------------------------

    public function __construct($providerRecord = null)
    {
        $this->providerRecord = $providerRecord;

        if (is_null($this->providerRecord)) {
            $this->providerRecord = Oauth_ProviderRecord::model();
        }
    }

    // --------------------------------------------------------------------

    public function providerConnect($provider)
    {

        $returnProvider = null;

        try {
            Craft::log(__METHOD__." : Provider processing", LogLevel::Info, true);

            $returnProvider = $provider->process(function($url, $token = null) {

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

            return false;
        }

        return $returnProvider;
    }

    // --------------------------------------------------------------------

    public function providerInstantiate($providerClass, $token = null, $scope = null, $callbackUrl = null)
    {
        // get provider record

        $providerRecord = $this->providerRecord($providerClass);


        if(!$callbackUrl) {
            $callbackUrl = UrlHelper::getSiteUrl(
                craft()->config->get('actionTrigger').'/oauth/public/connect',
                array('provider' => $providerClass)
            );
        }

        // provider options

        if($providerRecord) {
            $opts = array(
                'id' => $providerRecord->clientId,
                'secret' => $providerRecord->clientSecret,
                'redirect_url' => $callbackUrl
            );
        } else {
            $opts = array(
                'id' => 'x',
                'secret' => 'x',
                'redirect_url' => 'x'
            );
        }

        if($scope) {
            if(is_array($scope) && !empty($scope)) {
                $opts['scope'] = $scope;
            }
        }


        $class = "\\OAuth\\Provider\\{$providerClass}";

        $provider = new $class($opts);

        if($token) {
            $provider->setToken($token);

            craft()->oauth_tokens->tokenRefresh($provider);
        }

        return $provider;
    }

    // --------------------------------------------------------------------

    public function providerRecord($providerClass)
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

    public function providerIsConfigured($providerClass)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $record = Oauth_ProviderRecord::model()->find('providerClass=:providerClass', array(':providerClass' => $providerClass));

        if ($record) {
            Craft::log(__METHOD__." : Yes", LogLevel::Info, true);
            if(!empty($record->clientId) && !empty($record->clientSecret)) {
                return true;
            }
        }

        Craft::log(__METHOD__." : No", LogLevel::Info, true);

        return false;
    }

    // --------------------------------------------------------------------

    public function providerIsConnected($providerClass, $scope = null, $namespace = null)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $criteriaConditions = 'provider=:provider';
        $criteriaParams = array(':provider' => $providerClass);

        if(!$namespace) {
            $userId = craft()->userSession->user->id;

            $criteriaConditions .= ' AND userId=:userId';
            $criteriaParams[':userId'] = $userId;

        } else {
            $criteriaConditions .= ' AND namespace=:namespace';
            $criteriaParams[':namespace'] = $namespace;
        }

        $tokenRecord = Oauth_TokenRecord::model()->find($criteriaConditions, $criteriaParams);

        if($tokenRecord) {
            Craft::log(__METHOD__." : Token Record found", LogLevel::Info, true);

            // check scope (scopeIsEnough)

            return craft()->oauth->scopeIsEnough($scope, $tokenRecord->scope);
        }

        Craft::log(__METHOD__." : Token Record not found", LogLevel::Info, true);

        return false;
    }

    // --------------------------------------------------------------------

    public function providerSave(Oauth_ProviderModel $provider)
    {
        // save record

        $record = $this->_getProviderRecordById($provider->id);

        $record->providerClass = $provider->providerClass;
        $record->enabled = $provider->enabled;
        $record->clientId = $provider->clientId;
        $record->clientSecret = $provider->clientSecret;

        return $record->save(false);
    }

    // --------------------------------------------------------------------


    private function _getProviderRecordById($providerId = null)
    {
        if ($providerId)
        {
            $providerRecord = Oauth_ProviderRecord::model()->findById($providerId);

            if (!$providerRecord)
            {
                throw new Exception(Craft::t('No section exists with the ID “{id}”', array('id' => $providerId)));
            }
        }
        else
        {
            $providerRecord = new Oauth_ProviderRecord();
        }

        return $providerRecord;
    }


    // --------------------------------------------------------------------

    public function providerSaveOld(Oauth_ProviderModel &$model)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $class = $model->getAttribute('providerClass');

        if (null === ($record = Oauth_ProviderRecord::model()->find('providerClass=:providerClass', array(':providerClass' => $class)))) {
            $record = $this->providerRecord->create();
        }

        $record->setAttributes($model->getAttributes());

        if ($record->save()) {
            // update id on model (for new records)

            $model->setAttribute('id', $record->getAttribute('id'));

            return true;
        } else {

            $model->addErrors($record->getErrors());

            return false;
        }
    }

    // --------------------------------------------------------------------
}