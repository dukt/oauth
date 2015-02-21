<?php

/**
 * Craft OAuth by Dukt
 *
 * @package   Craft OAuth
 * @author    Benjamin David
 * @copyright Copyright (c) 2015, Dukt
 * @link      https://dukt.net/craft/oauth/
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Craft;

class OauthController extends BaseController
{
    protected $allowAnonymous = array('actionConnect');

    private $handle;
    private $namespace;
    private $scopes;
    private $params;
    private $redirect;
    private $referer;

    /**
     * Edit Provider
     *
     * @return null
     */
    public function actionProviderInfos(array $variables = array())
    {
        if(!empty($variables['handle']))
        {
            $provider = craft()->oauth->getProvider($variables['handle'], false, true);

            if($provider)
            {
                $variables['infos'] = craft()->oauth->getProviderInfos($variables['handle']);;
                $variables['provider'] = $provider;
                $variables['configInfos'] = craft()->config->get('oauth');
                $variables['configInfos'] = $variables['configInfos'][$variables['handle']];

                $this->renderTemplate('oauth/_provider', $variables);
            }
            else
            {
                throw new HttpException(404, $exception->getMessage());
            }
        }
        else
        {
            throw new HttpException(404, $exception->getMessage());
        }
    }

    /**
     * Save provider.
     *
     * @return null
     */
    public function actionProviderSave()
    {
        $handle = craft()->request->getParam('handle');
        $attributes = craft()->request->getPost('provider');

        $provider = craft()->oauth->getProvider($handle, false);

        $providerInfos = new Oauth_ProviderInfosModel($attributes);
        $providerInfos->id = craft()->request->getParam('providerId');
        $providerInfos->class = $handle;

        if (craft()->oauth->providerSave($providerInfos))
        {
            craft()->userSession->setNotice(Craft::t('Service saved.'));
            $redirect = craft()->request->getPost('redirect');
            $this->redirect($redirect);
        }
        else
        {
            craft()->userSession->setError(Craft::t("Couldn't save service."));
            craft()->urlManager->setRouteVariables(array('infos' => $providerInfos));
        }
    }

    /**
     * Delete token.
     *
     * @return null
     */
    public function actionDeleteToken(array $variables = array())
    {
        $this->requirePostRequest();

        $id = craft()->request->getRequiredPost('id');

        $token = craft()->oauth->getTokenById($id);

        if (craft()->oauth->deleteToken($token))
        {
            if (craft()->request->isAjaxRequest())
            {
                $this->returnJson(array('success' => true));
            }
            else
            {
                craft()->userSession->setNotice(Craft::t('Token deleted.'));
                $this->redirectToPostedUrl($token);
            }
        }
        else
        {
            if (craft()->request->isAjaxRequest())
            {
                $this->returnJson(array('success' => false));
            }
            else
            {
                craft()->userSession->setError(Craft::t('Couldn’t delete token.'));

                // Send the token back to the template
                craft()->urlManager->setRouteVariables(array(
                    'token' => $token
                ));
            }
        }
    }


/*
    handle
    referer
    params
    response
    scopes
*/

/*
    requestUri
    social
    socialToken
    socialUser
    socialUid
    socialProviderHandle
    socialRedirect
*/


    /**
     * Connect
     *
     * @return null
     */
    public function actionConnect()
    {
        $error = false;
        $success = false;
        $token = false;
        $errorMsg = false;

        try
        {
            // handle
            $this->handle = craft()->httpSession->get('oauth.handle');

            if(!$this->handle)
            {
                $this->handle = craft()->request->getParam('provider');
                craft()->httpSession->add('oauth.handle', $this->handle);
            }


            // session vars

            $this->scopes = craft()->httpSession->get('oauth.scopes');
            $this->params = craft()->httpSession->get('oauth.params');
            $this->referer = craft()->httpSession->get('oauth.referer');


            // google cancel

            if(craft()->request->getParam('error'))
            {
                throw new Exception("An error occured: ".craft()->request->getParam('error'));
            }


            // twitter cancel

            if(craft()->request->getParam('denied'))
            {
                throw new Exception("An error occured: ".craft()->request->getParam('denied'));
            }


            // provider

            $provider = craft()->oauth->getProvider($this->handle);
            $provider->setScopes($this->scopes);


            // init service

            switch($provider->oauthVersion)
            {
                case 2:
                    // oauth 2

                    $code = craft()->request->getParam('code');

                    if (!$code)
                    {
                        // redirect to authorization url if we don't have a code yet

                        $authorizationUrl = $provider->getAuthorizationUri($this->params);

                        $this->redirect($authorizationUrl);
                    }
                    else
                    {
                        // get token from code
                        $token = $provider->requestAccessToken($code);
                    }

                    break;

                case 1:

                    // oauth 1

                    $oauth_token = craft()->request->getParam('oauth_token');
                    $oauth_verifier = craft()->request->getParam('oauth_verifier');

                    if (!$oauth_token)
                    {
                        // redirect to authorization url if we don't have a oauth_token yet

                        $token = $provider->requestRequestToken();
                        $authorizationUrl = $provider->getAuthorizationUri(array('oauth_token' => $token->getRequestToken()));
                        $this->redirect($authorizationUrl);
                    }
                    else
                    {
                        // get token from oauth_token
                        $token = $provider->retrieveAccessToken();

                        // This was a callback request, now get the token
                        $token = $provider->requestAccessToken(
                            $oauth_token,
                            $oauth_verifier,
                            $token->getRequestTokenSecret()
                        );

                        if(!$token->getAccessToken())
                        {
                            throw new Exception("Couldn't retrieve token");
                        }
                    }

                break;

                default:
                    throw new Exception("Couldn't handle connect for this provider");
            }

            $success = true;
        }
        catch(\Exception $e)
        {
            $error = true;
            $errorMsg = $e->getMessage();
        }


        // we now have $token, build up response

        $tokenArray = null;

        if($token)
        {
            $tokenArray = craft()->oauth->realTokenToArray($token);
        }

        $response = array(
            'error'         => $error,
            'errorMsg'      => $errorMsg,
            'success'       => $success,
            'token'         => $tokenArray
        );

        craft()->httpSession->add('oauth.response', $response);


        // redirect
        $this->redirect($this->referer);
    }


    // Console
    // -------------------------------------------------------------------------

    /**
     * Console
     *
     * @return null
     */
    public function actionConsole(array $variables = array())
    {
        $providers = craft()->oauth->getProviders(false);

        $tokens = array();

        foreach($providers as $provider)
        {
            $token = craft()->httpSession->get('oauth.console.token.'.$provider->getHandle());

            if($token)
            {
                $tokens[$provider->getHandle()] = true;
            }
        }
        $variables['providers'] = $providers;
        $variables['tokens'] = $tokens;

        $this->renderTemplate('oauth/console/index', $variables);
    }
    /**
     * Console
     *
     * @return null
     */
    public function actionConsoleProvider(array $variables = array())
    {
        // require_once(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');

        $handle = $variables['providerHandle'];


        // token

        $token = false;
        $tokenArray = craft()->httpSession->get('oauth.console.token.'.$handle);

        if($tokenArray)
        {
            $token = craft()->oauth->arrayToToken($tokenArray);
        }




        // provider

        $provider = craft()->oauth->getProvider($handle);


        // render

        $variables['provider'] = $provider;
        $variables['token'] = $token;

        $this->renderTemplate('oauth/console/_provider', $variables);
    }

    /**
     * Console connect
     *
     * @return null
     */
    public function actionConsoleConnect()
    {
        $referer = craft()->request->getUrlReferrer();
        $providerHandle = craft()->request->getParam('provider');

        craft()->httpSession->add('oauth.console.referer', $referer);
        craft()->httpSession->add('oauth.console.providerHandle', $providerHandle);
        craft()->httpSession->remove('oauth.console.token.'.$providerHandle);

        $this->redirect(UrlHelper::getActionUrl('oauth/consoleConnectStep2'));
    }

    /**
     * Console Connect Step 2
     *
     * @return null
     */
    public function actionConsoleConnectStep2()
    {
        $providerHandle = craft()->httpSession->get('oauth.console.providerHandle');
        $referer = craft()->httpSession->get('oauth.console.referer');


        // connect

        $provider = craft()->oauth->getProvider($providerHandle);

        $scopes = $provider->getScopes();
        $params = $provider->getParams();

        if($response = craft()->oauth->connect(array(
            'plugin' => 'oauth',
            'provider' => $providerHandle,
            'scopes' => $scopes,
            'params' => $params
        )))
        {
            if($response['success'])
            {
                // token
                $token = $response['token'];

                $tokenArray = craft()->oauth->tokenToArray($token);

                // save token
                craft()->httpSession->add('oauth.console.token.'.$providerHandle, $tokenArray);

                // session notice
                craft()->userSession->setNotice(Craft::t("Connected."));
            }
            else
            {
                craft()->userSession->setError(Craft::t($response['errorMsg']));
            }
        }
        else
        {
            // session error
            craft()->userSession->setError(Craft::t("Couldn’t connect"));
        }


        // redirect

        $this->redirect($referer);
    }

    /**
     * Console disconnect
     *
     * @return null
     */
    public function actionConsoleDisconnect()
    {
        $providerHandle = craft()->request->getParam('provider');

        // reset token
        craft()->httpSession->remove('oauth.console.token.'.$providerHandle);

        // set notice
        craft()->userSession->setNotice(Craft::t("Disconnected."));

        // redirect
        $redirect = craft()->request->getUrlReferrer();
        $this->redirect($redirect);
    }

}