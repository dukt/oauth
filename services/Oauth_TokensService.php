<?php

namespace Craft;

require_once(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');

use ReflectionClass;
use Symfony\Component\Finder\Finder;

class Oauth_TokensService extends BaseApplicationComponent
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

    public function tokenDeleteById($id)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $record = Oauth_TokenRecord::model()->findByPk($id);

        if($record) {
            return $record->delete();
        }

        return false;
    }

    // --------------------------------------------------------------------

    public function tokenScopeByCurrentUser($providerClass)
    {
        // provider record

        $providerRecord = craft()->oauth_providers->providerRecord($providerClass);

        if($providerRecord) {

            // user token record

            $tokenRecord = $this->tokenRecordByCurrentUser($providerClass);

            if($tokenRecord) {

                $tokenScope = @unserialize(base64_decode($tokenRecord->scope));

                return $tokenScope;
            }
        }

        return null;
    }

    // --------------------------------------------------------------------
    // Deprecated ?
    // --------------------------------------------------------------------

    public function tokenScopeByNamespace($providerClass, $namespace)
    {
        // provider record

        $providerRecord = $this->providerRecord($providerClass);

        if($providerRecord) {

            // user token record

            $tokenRecord = $this->tokenRecordByNamespace($providerClass, $namespace);

            if($tokenRecord) {

                $tokenScope = @unserialize(base64_decode($tokenRecord->scope));

                return $tokenScope;
            }
        }

        return null;
    }

    // --------------------------------------------------------------------

    public function tokenRecordByUser($providerClass, $userId)
    {
        if(!craft()->userSession->user) {
            return null;
        }

        $conditions = 'provider=:provider';
        $params = array(':provider' => $providerClass);

        $conditions .= ' AND userId=:userId';
        $params[':userId'] = $userId;

        $tokenRecord = Oauth_TokenRecord::model()->find($conditions, $params);

        if($tokenRecord) {
            return $tokenRecord;
        }

        return null;
    }

    // --------------------------------------------------------------------

    public function tokenRecordByCurrentUser($providerClass)
    {
        if(!craft()->userSession->user) {
            return null;
        }

        $conditions = 'provider=:provider';
        $params = array(':provider' => $providerClass);

        $conditions .= ' AND userId=:userId';
        $params[':userId'] = craft()->userSession->user->id;

        $tokenRecord = Oauth_TokenRecord::model()->find($conditions, $params);

        if($tokenRecord) {
            return $tokenRecord;
        }

        return null;
    }

    // --------------------------------------------------------------------

    public function tokenRecordByNamespace($providerClass, $namespace)
    {
        $conditions = 'provider=:provider AND namespace=:namespace';
        $params = array(
                ':provider' => $providerClass,
                ':namespace' => $namespace,
            );

        $tokenRecord = Oauth_TokenRecord::model()->find($conditions, $params);

        return $tokenRecord;
    }

    // --------------------------------------------------------------------

    public function tokenRefresh($provider)
    {
        $difference = ($provider->token->expires - time());

        // token expired : we need to refresh it

        if($difference < 1) {

            Craft::log(__METHOD__." : Refresh token ", LogLevel::Info, true);

            $encodedToken = base64_encode(serialize($provider->token));

            $tokenRecord = craft()->oauth->getTokenEncoded($encodedToken);


            if(method_exists($provider, 'access') && $provider->token->refresh_token) {

                $accessToken = $provider->access($provider->token->refresh_token, array('grant_type' => 'refresh_token'));

                if(!$accessToken) {
                    Craft::log(__METHOD__." : Could not refresh token", LogLevel::Info, true);
                }
                // save token

                $provider->token->access_token = $accessToken->access_token;
                $provider->token->expires = $accessToken->expires;

                $tokenRecord->token = base64_encode(serialize($provider->token));

                if($tokenRecord->save()) {
                    Craft::log(__METHOD__." : Token saved", LogLevel::Info, true);
                }
            } else {
                Craft::log(__METHOD__." : Access method (for refresh) doesn't exists for this provider", LogLevel::Info, true);
            }
        }
    }

    // --------------------------------------------------------------------
    // deprecated ?
    // --------------------------------------------------------------------

    public function tokenReset($providerClass)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

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

    public function tokensByProvider($provider, $user = false)
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        if($user) {
            // $userId = craft()->userSession->user->id;

            $userId = $user;

            $criteriaConditions = '
                provider=:provider AND
                userId=:userId
                ';

            $criteriaParams = array(
                ':userId' => $userId,
                ':provider' => $provider,
                );
        } else {
            $criteriaConditions = '
                provider=:provider
                ';

            $criteriaParams = array(
                ':provider' => $provider
                );
        }

        $tokens = Oauth_TokenRecord::model()->findAll($criteriaConditions, $criteriaParams);

        return $tokens;
    }

    // --------------------------------------------------------------------
}