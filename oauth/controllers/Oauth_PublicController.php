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

    public function actionConnect()
    {
        // handle
        $this->handle = craft()->request->getParam('provider');


        // session vars
        $this->redirect = craft()->httpSession->get('oauth.redirect');
        $this->scopes = craft()->httpSession->get('oauth.scopes');
        $this->params = craft()->httpSession->get('oauth.params');


        // provider

        $provider = craft()->oauth->getProvider($this->handle);

        // init service
        $provider->source->initService($this->scopes);

        switch($this->handle)
        {
            case 'google':

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

            case 'twitter':

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
                    $token = $provider->source->storage->retrieveAccessToken('Twitter');

                    // This was a callback request from twitter, get the token
                    $token = $provider->source->service->requestAccessToken(
                        $oauth_token,
                        $oauth_verifier,
                        $token->getRequestTokenSecret()
                    );
                }

            break;

            default:
                throw new Exception("Couldn't handle connect for this provider");


        }

        // ... token now ready to be used, trigger some event ?

        // Fire an 'onConnect' event
        craft()->oauth->onConnect(new Event($this, array(
            'provider' => $provider,
            'token'      => $token
        )));

        $this->redirect($this->redirect);
    }
}
