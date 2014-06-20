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
    private $redirect;
    private $referer;
    private $errorRedirect;

    public function actionConnect()
    {
        $error = false;
        $success = false;
        $token = false;
        $errorMsg = false;

        try
        {
            // handle
            $this->handle = craft()->request->getParam('provider');


            // session vars

            $this->redirect = craft()->httpSession->get('oauth.redirect');
            $this->errorRedirect = craft()->httpSession->get('oauth.errorRedirect');
            $this->scopes = craft()->httpSession->get('oauth.scopes');
            $this->params = craft()->httpSession->get('oauth.params');
            $this->referer = craft()->httpSession->get('oauth.referer');


            // provider

            $provider = craft()->oauth->getProvider($this->handle);

            // init service
            $provider->source->initializeService($this->scopes);
            $classname = get_class($provider->source->service);

            switch($classname::OAUTH_VERSION)
            {
                case 2:

                    // oauth 2

                    $code = craft()->request->getParam('code');

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
                    }

                    break;

                case 1:

                    // oauth 1

                    $oauth_token = craft()->request->getParam('oauth_token');
                    $oauth_verifier = craft()->request->getParam('oauth_verifier');

                    if (!$oauth_token)
                    {
                        // redirect to authorization url if we don't have a oauth_token yet

                        $token = $provider->source->service->requestRequestToken();
                        $authorizationUrl = $provider->source->service->getAuthorizationUri(array('oauth_token' => $token->getRequestToken()));
                        $this->redirect($authorizationUrl);
                    }
                    else
                    {
                        // get token from oauth_token
                        $token = $provider->source->storage->retrieveAccessToken($provider->source->getClass());

                        // This was a callback request, now get the token
                        $token = @$provider->source->service->requestAccessToken(
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

        $response = array(
            'error'         => $error,
            'errorMsg'      => $errorMsg,
            'errorRedirect' => $this->errorRedirect,
            'redirect'      => $this->redirect,
            'success'       => $success,
            'token'         => $token
        );

        craft()->httpSession->add('oauth.response', $response);

        $this->redirect($this->referer);

    }
}
