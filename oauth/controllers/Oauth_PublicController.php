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

    private function actionConnect()
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

            // ... token now ready to be used, trigger some event ?

            // Fire an 'onConnect' event
            craft()->oauth->onConnect(new Event($this, array(
                'token'      => $token
            )));
        }

        $this->redirect($this->referer);
    }


















    public function deprecated_actionConnect()
    {
        // handle
        $this->handle = craft()->request->getParam('provider');

        // session vars
        $this->referer = craft()->httpSession->get('oauth.referer');
        $this->namespace = craft()->httpSession->get('oauth.namespace');
        $this->scopes = craft()->httpSession->get('oauth.scopes');
        $this->params = craft()->httpSession->get('oauth.params');


        // connect user or system


        if($this->namespace)
        {
            $this->connectSystem();
        }
        else
        {
            $this->connectUser();
        }

    }

    private function connectUser()
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
            $realToken = $provider->source->service->requestAccessToken($code);

            // current user
            $user = craft()->userSession->getUser();


            // retrieve user

            $account = false;

            if($realToken)
            {
                $provider->source->setRealToken($realToken);
                $account = $provider->getAccount();
            }

            $token = craft()->oauth->getTokenFromUserMapping($this->handle, $account['uid']);

            if($user && $token)
            {
                if ($user->id != $token->userId)
                {
                    // error because uid is associated with another user
                    throw new Exception("uid is already associated with another user. Disconnect from your current session and retry.");
                }
            }


            // save token

            if(!$token)
            {
                $token = new Oauth_TokenModel();

                if($user)
                {
                    $token->userId = $user->id;
                }
            }

            $token->provider = $this->handle;
            $token->token = base64_encode(serialize($realToken));
            $token->userMapping = $account['uid'];
            $token->scope = $this->scopes;


            // Fire an 'onBeforeSaveToken' event
            craft()->oauth->onBeforeSaveUserToken(new Event($this, array(
                'user'      => $user,
                'account'   => $account,
                'token'     => $token
            )));

            // save token
            craft()->oauth->tokenSave($token);

            // Fire an 'onConnectUser' event
            craft()->oauth->onConnectUser(new Event($this, array(
                'realToken'      => $realToken
            )));

            // redirect
            $this->redirect($this->referer);
        }
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

}
