<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2015, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Craft;

require_once(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');

use ReflectionClass;
use Guzzle\Http\Client;

class OauthService extends BaseApplicationComponent
{
    // Properties
    // =========================================================================

    private $_configuredProviders = array();
    private $_allProviders = array();
    private $_providersLoaded = false;

    // Public Methods
    // =========================================================================

    /**
     * Connect
     */
    public function connect($variables)
    {
        if(!craft()->httpSession->get('oauth.response'))
        {
            // we don't have any response yet, get ready to connect

            // clean session

            $this->_sessionClean();


            // get provider

            $provider = craft()->oauth->getProvider($variables['provider']);

            // referer

            if(!empty($variables['referer']))
            {
                $referer = $variables['referer'];
            }
            else
            {
                $referer = craft()->request->getUrl();
            }

            craft()->httpSession->add('oauth.referer', $referer);


            // scopes

            if(!empty($variables['scopes']))
            {
                $scopes = $variables['scopes'];
            }
            else
            {
                $scopes = $provider->getScopes();
            }

            craft()->httpSession->add('oauth.scopes', $scopes);


            // params

            if(!empty($variables['params']))
            {
                $params = $variables['params'];
            }
            else
            {
                $params = $provider->getParams();
            }

            craft()->httpSession->add('oauth.params', $params);

            // redirect
            craft()->request->redirect(UrlHelper::getActionUrl('oauth/connect/', array(
                'provider' => $variables['provider']
            )));
        }
        else
        {
            // populate token object from response

            $response = craft()->httpSession->get('oauth.response');

            if(!empty($response['token']))
            {
                // response token to token model

                $token = new Oauth_TokenModel;

                $provider = $this->getProvider($variables['provider']);

                if($provider)
                {
                    switch ($provider->oauthVersion) {
                        case 1:

                            if(!empty($response['token']['identifier']))
                            {
                                $token->accessToken = $response['token']['identifier'];
                            }

                            if(!empty($response['token']['secret']))
                            {
                                $token->secret = $response['token']['secret'];
                            }

                            break;

                        case 2:

                            if(!empty($response['token']['accessToken']))
                            {
                                $token->accessToken = $response['token']['accessToken'];
                            }

                            if(!empty($response['token']['expires']))
                            {
                                $token->endOfLife = $response['token']['expires'];
                            }

                            if(!empty($response['token']['refreshToken']))
                            {
                                $token->refreshToken = $response['token']['refreshToken'];
                            }

                            break;
                    }
                }

                $token->providerHandle = $variables['provider'];
                $token->pluginHandle = $variables['plugin'];

                $response['token'] = $token;
            }

            $this->_sessionClean();

            return $response;
        }
    }

    /**
     * Get provider
     */
    public function getProvider($handle,  $configuredOnly = true, $fromRecord = false)
    {
        $this->_loadProviders($fromRecord);

        $lcHandle = strtolower($handle);

        if($configuredOnly)
        {
            if(isset($this->_configuredProviders[$lcHandle]))
            {
                return $this->_configuredProviders[$lcHandle];
            }
        }
        else
        {
            if(isset($this->_allProviders[$lcHandle]))
            {
                return $this->_allProviders[$lcHandle];
            }
        }

        return null;
    }

    /**
     * Get providers
     */
    public function getProviders($configuredOnly = true)
    {
        $this->_loadProviders();

        if($configuredOnly)
        {
            return $this->_configuredProviders;
        }
        else
        {
            return $this->_allProviders;
        }
    }

    /**
     * Save provider
     */
    public function providerSave(Oauth_ProviderInfosModel $model)
    {
        // save record

        $record = $this->_getProviderInfosRecordById($model->id);
        $record->class = $model->class;
        $record->clientId = $model->clientId;
        $record->clientSecret = $model->clientSecret;

        return $record->save(false);
    }

    /**
     * Get provider infos
     */
    public function getProviderInfos($handle)
    {
        $record = $this->_getProviderRecordByHandle($handle);

        if($record)
        {
            return Oauth_ProviderInfosModel::populateModel($record);
        }
    }

    /**
     * Get token by ID
     */
    public function getTokenById($id)
    {
        if ($id)
        {
            $record = Oauth_TokenRecord::model()->findById($id);

            if ($record)
            {
                $token = Oauth_TokenModel::populateModel($record);

                // will refresh token if needed

                try
                {
                    if($this->_refreshToken($token))
                    {
                        // save refreshed token
                        $this->saveToken($token);
                    }
                }
                catch(\Exception $e)
                {
                    // todo: log
                    // throw $e;

                    // return null;
                }

                return $token;
            }
        }
    }

    /**
     * Get tokens by provider
     */
    public function getTokensByProvider($providerHandle)
    {
        $conditions = 'providerHandle=:providerHandle';
        $params = array(':providerHandle' => $providerHandle);
        $records = Oauth_TokenRecord::model()->findAll($conditions, $params);
        return Oauth_TokenModel::populateModels($records);
    }

    /**
     * Save token
     */
    public function saveToken(Oauth_TokenModel &$model)
    {
        // is new ?
        $isNewToken = !$model->id;

        // populate record
        $record = $this->_getTokenRecordById($model->id);
        $record->providerHandle = strtolower($model->providerHandle);
        $record->pluginHandle = strtolower($model->pluginHandle);
        $record->accessToken = $model->accessToken;
        $record->secret = $model->secret;
        $record->endOfLife = $model->endOfLife;
        $record->refreshToken = $model->refreshToken;

        // save record
        if($record->save(false))
        {
            // populate id
            if($isNewToken)
            {
                $model->id = $record->id;
            }

            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Delete token ID
     */
    public function deleteToken(Oauth_TokenModel $token)
    {
        if (!$token->id)
        {
            return false;
        }

        $record = Oauth_TokenRecord::model()->findById($token->id);

        if($record)
        {
            return $record->delete();
        }

        return false;
    }

    /**
     * Delete tokens by plugin
     */
    public function deleteTokensByPlugin($pluginHandle)
    {
        $conditions = 'pluginHandle=:pluginHandle';
        $params = array(':pluginHandle' => $pluginHandle);
        return Oauth_TokenRecord::model()->deleteAll($conditions, $params);
    }


    // Private Methods
    // =========================================================================

    /**
     * Get token record by ID
     */
    private function _getTokenRecordById($id = null)
    {
        if ($id)
        {
            $record = Oauth_TokenRecord::model()->findById($id);

            if (!$record)
            {
                throw new Exception(Craft::t('No oauth token exists with the ID “{id}”', array('id' => $id)));
            }
        }
        else
        {
            $record = new Oauth_TokenRecord();
        }

        return $record;
    }

    /**
     * Refresh token
     */
    private function _refreshToken(Oauth_TokenModel $model)
    {
        $time = time();

        // force refresh for testing
        // $time = time() + 3595; // google ttl
        // $time = time() + 50400005089; // facebook ttl

        $provider = craft()->oauth->getProvider($model->providerHandle);


        // Refreshing the token only applies to OAuth 2.0 providers

        if($provider && $provider->oauthVersion == 2)
        {
            // Has token expired ?

            if($time > $model->endOfLife)
            {
                $realToken = OauthHelper::getRealToken($model);

                $infos = $provider->getInfos();

                $refreshToken = $realToken->refreshToken;

                $grant = new \League\OAuth2\Client\Grant\RefreshToken();
                $newToken = $provider->getProvider()->getAccessToken($grant, ['refresh_token' => $refreshToken]);

                if($newToken)
                {
                    $model->accessToken = $newToken->accessToken;
                    $model->endOfLife = $newToken->expires;

                    $newRefreshToken = $newToken->refreshToken;

                    if(!empty($newRefreshToken))
                    {
                        $model->refreshToken = $newToken->refreshToken;
                    }

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Clean session
     */
    public function _sessionClean()
    {
        craft()->httpSession->remove('oauth.handle');
        craft()->httpSession->remove('oauth.referer');
        craft()->httpSession->remove('oauth.params');
        craft()->httpSession->remove('oauth.redirect');
        craft()->httpSession->remove('oauth.response');
        craft()->httpSession->remove('oauth.scopes');
    }

    /**
     * Get provider record by handle
     */
    private function _getProviderRecordByHandle($handle)
    {
        $providerRecord = Oauth_ProviderInfosRecord::model()->find(

            // conditions
            'class=:provider',

            // params
            array(
                ':provider' => $handle
            )
        );

        if($providerRecord)
        {
            return $providerRecord;
        }

        return null;
    }

    /**
     * Get provider infos record by ID
     */
    private function _getProviderInfosRecordById($providerId = null)
    {
        if ($providerId)
        {
            $providerRecord = Oauth_ProviderInfosRecord::model()->findById($providerId);

            if (!$providerRecord)
            {
                throw new Exception(Craft::t('No oauth provider exists with the ID “{id}”', array('id' => $providerId)));
            }
        }
        else
        {
            $providerRecord = new Oauth_ProviderInfosRecord();
        }

        return $providerRecord;
    }

    /**
     * Loads the configured providers.
     */
    private function _loadProviders($fromRecord = false)
    {
        if($this->_providersLoaded)
        {
            return;
        }

        $providerSources = $this->_getProviders();

        foreach($providerSources as $providerSource)
        {
            // handle
            $handle = $providerSource->getHandle();

            // get provider record
            $record = $this->_getProviderRecordByHandle($providerSource->getHandle());

            // create provider (from record if any)
            $providerInfos = Oauth_ProviderInfosModel::populateModel($record);


            // override provider infos from config

            $oauthConfig = craft()->config->get('oauth');

            if($oauthConfig && !$fromRecord)
            {
                if(!empty($oauthConfig[$providerSource->getHandle()]['clientId']))
                {
                    $providerInfos->clientId = $oauthConfig[$providerSource->getHandle()]['clientId'];
                }

                if(!empty($oauthConfig[$providerSource->getHandle()]['clientSecret']))
                {
                    $providerInfos->clientSecret = $oauthConfig[$providerSource->getHandle()]['clientSecret'];
                }
            }

            $providerSource->setInfos($providerInfos);

            if($providerSource->isConfigured())
            {
                $this->_configuredProviders[$handle] = $providerSource;
            }

            // add to _allProviders array
            $this->_allProviders[$handle] = $providerSource;
        }

        // providers are now loaded
        $this->_providersLoaded = true;
    }

    /**
     * Get Providers
     */
    private function _getProviders()
    {
        // fetch all OAuth provider types

        $oauthProviderTypes = array();

        foreach(craft()->plugins->call('getOAuthProviders', [], true) as $pluginOAuthProviderTypes)
        {
            $oauthProviderTypes = array_merge($oauthProviderTypes, $pluginOAuthProviderTypes);
        }


        // instantiate providers

        $providers = [];

        foreach($oauthProviderTypes as $oauthProviderType)
        {
            $providers[$oauthProviderType] = $this->_createProvider($oauthProviderType);
        }

        ksort($providers);

        return $providers;
    }

    /**
     * Create OAuth provider
     */
    private function _createProvider($oauthProviderType)
    {
        $oauthProvider = new $oauthProviderType;

        return $oauthProvider;
    }
}