<?php

/**
 * Craft OAuth by Dukt
 *
 * @package   Craft OAuth
 * @author    Benjamin David
 * @copyright Copyright (c) 2014, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 * @link      https://dukt.net/craft/oauth/
 */

namespace Craft;

class Oauth_PublicController extends BaseController
{
    protected $allowAnonymous = true;

    private $handle;
    private $namespace;
    private $scopes;
    private $params;
    private $referer;

    public function actionConnect()
    {
        // handle
        $this->handle = craft()->request->getParam('provider');

        // session vars
        $this->referer = craft()->httpSession->get('oauth.referer');
        $this->namespace = craft()->httpSession->get('oauth.namespace');
        $this->scopes = craft()->httpSession->get('oauth.scopes');
        $this->params = craft()->httpSession->get('oauth.params');


        // connect user or system

        try
        {
            if($this->namespace)
            {
                $this->connectSystem();
            }
            else
            {
                $this->connectUser();
            }
        }
        catch(\Exception $e)
        {
            die('error:'.$e->getMessage());
            $userSession->setError(Craft::t($e->getMessage()));
            craft()->httpSession->add('error', Craft::t($e->getMessage()));
            $this->redirect($referer);
        }
    }

    public function actionDisconnect()
    {
        Craft::log("OAuth Disconnect", LogLevel::Info);

        // redirect url

        $redirect = craft()->httpSession->get('oauth.referer');

        if(!$redirect)
        {
            $redirect = $_SERVER['HTTP_REFERER'];
        }

        // get request params

        $providerHandle = craft()->request->getParam('provider');
        $namespace = craft()->request->getParam('namespace');

        if($namespace)
        {
            $userMode = false;
        }
        else
        {
            if(craft()->userSession->user)
            {
                $userMode = true;
            }
            else
            {
                $this->redirect($redirect);
            }
        }

        // remove cache

        if($namespace)
        {
            $token = craft()->oauth->getSystemToken($providerHandle, $namespace);

            if($token)
            {
                $token = $token->getRealToken();
            }
        }



        // criteria conditions & params

        $conditions = 'provider=:provider';
        $params = array(':provider' => $providerHandle);

        if($namespace)
        {
            $conditions .= ' AND namespace=:namespace';
            $params[':namespace']  = $namespace;
        }

        if($userMode)
        {
            $conditions .= ' AND userId=:userId';
            $params[':userId']  = craft()->userSession->user->id;
        }

        // delete all matching records
        Oauth_TokenRecord::model()->deleteAll($conditions, $params);

        // redirect
        $this->redirect($redirect);
    }

    private function connectUser()
    {
        Craft::log("Connect User", LogLevel::Info);

        // session variables

        $opts = array();

        $providerHandle = $opts['oauth.providerClass'] = craft()->httpSession->get('oauth.providerClass');
        $social         = $opts['oauth.social'] = craft()->httpSession->get('oauth.social');
        $socialCallback = $opts['oauth.socialCallback'] = craft()->httpSession->get('oauth.socialCallback');
        $referer        = $opts['oauth.referer'] = craft()->httpSession->get('oauth.referer');

        $token = craft()->oauth->getToken($providerHandle);
        $provider = craft()->oauth->getProvider($providerHandle);


        // scope

        $scope = craft()->httpSession->get('oauth.scope');

        if(!$scope)
        {
            // tokenScope
            $tokenScope = @unserialize(base64_decode($token->scope));

            // is scope enough ?
            $scopeEnough = craft()->oauth->scopeIsEnough($scope, $tokenScope);


            // scope not enough? connect user with new scope

            if(!$scopeEnough)
            {
                $scope = craft()->oauth->scopeMix($scope, $tokenScope);
                craft()->httpSession->add('oauth.scope', $scope);
            }
        }

        $opts['oauth.scope'] = $scope;


        // instantiate provider

        if(!$provider)
        {
            craft()->userSession->setError(Craft::t("Provider not configured."));
            craft()->httpSession->add('error', Craft::t("Provider not configured."));
            $this->redirect($referer);
            return;
        }

        try
        {
            $provider->connectScope($scope);
        }
        catch(\Exception $e)
        {
            craft()->userSession->setError(Craft::t($e->getMessage()));
            craft()->httpSession->add('error', Craft::t($e->getMessage()));
            $this->redirect($referer);
        }


        // post-connect

        if($provider)
        {
            // ----------------------
            // social bypass
            // ----------------------

            $oauthToken = $opts['oauth.token'] = base64_encode(serialize($provider->getToken()));

            if($social)
            {
                if(isset(craft()->social))
                {
                    $redirect = craft()->social->loginCallback($opts);
                }
                else
                {
                    $redirect = craft()->socialize->loginCallback($opts);
                }

                $this->redirect($redirect);

                return;
            }


            // ----------------------
            // save userToken record
            // ----------------------

            // craft user must be logged in
            if(!craft()->userSession->user)
            {
                return null;
            }

            // set default scope if none set
            // if(!$scope)
            // {
            //     $scope = $provider->getScope();
            // }

            try
            {
                $account = $provider->getAccount();
            }
            catch(\Exception $e)
            {
                craft()->userSession->setError(Craft::t($e->getMessage()));
                craft()->httpSession->add('error', Craft::t($e->getMessage()));
                $this->redirect($referer);
            }


            // save token
            $token = craft()->oauth->getTokenFromUserMapping($providerHandle, $account['uid']);

            if($token)
            {
                if($token->userId != craft()->userSession->user->id)
                {
                    Craft::log($provider->name." account already used by another user.", LogLevel::Warning);

                    // cp errors

                    craft()->userSession->setError(Craft::t($provider->name." account already used by another user."));
                    craft()->httpSession->add('error', Craft::t($provider->name." account already used by another user."));

                    // template errors

                    $this->redirect($referer);

                    return null;
                }
            }
            else
            {
                $token = new Oauth_TokenModel;
            }

            $token->userId = craft()->userSession->user->id;
            $token->provider = strtolower($providerHandle);
            $token->userMapping = $account['uid'];
            $token->token = base64_encode(serialize($provider->getToken()));
            $token->scope = $scope;

            craft()->oauth->tokenSave($token);
        }
        else
        {
            Craft::log('Could not post-connect user', LogLevel::Error);
        }

        // remove httpSession variables
        craft()->oauth->sessionClean();

        // redirect
        $this->redirect($referer);
    }

    private function connectSystem()
    {
        $code = craft()->request->getParam('code');
        $provider = craft()->oauth->getProvider($this->handle);

        // init service
        $provider->source->initService($this->scopes);

        if (!$code)
        {
            // redirect to authorization url if we don't have a code yet

            $authorizationUrl = $provider->source->service->getAuthorizationUri($this->params);

            $this->redirect($authorizationUrl);
        }
        else
        {
            // get token from code
            $token = $provider->source->service->requestAccessToken($code);

            // remove any existing token with this namespace
            craft()->oauth->tokenDeleteByNamespace($this->handle, $this->namespace);


            // save token

            $model = new Oauth_TokenModel();
            $model->provider = $this->handle;
            $model->namespace = $this->namespace;
            $model->token = base64_encode(serialize($token));
            $model->scope = $this->scopes;

            craft()->oauth->tokenSave($model);
        }

        $this->redirect($this->referer);
    }
}
