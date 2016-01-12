<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2016, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Craft;

class Oauth_ConsoleController extends BaseController
{
    // Public Methods
    // =========================================================================

    /**
     * Index
     *
     * @return null
     */
    public function actionIndex(array $variables = array())
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

        $this->renderTemplate('oauth/console/_index', $variables);
    }

    /**
     * Provider
     *
     * @return null
     */
    public function actionProvider(array $variables = array())
    {
        $handle = $variables['providerHandle'];


        // token

        $token = false;
        $tokenArray = craft()->httpSession->get('oauth.console.token.'.$handle);

        if($tokenArray)
        {
            $token = OauthHelper::arrayToToken($tokenArray);
        }

        // provider

        $provider = craft()->oauth->getProvider($handle);


        // render

        $variables['provider'] = $provider;
        $variables['token'] = $token;

        $this->renderTemplate('oauth/console/_provider', $variables);
    }

    /**
     * Connect
     *
     * @return null
     */
    public function actionConnect()
    {
        $referer = craft()->request->getUrlReferrer();
        $providerHandle = craft()->request->getParam('provider');

        craft()->httpSession->add('oauth.console.referer', $referer);
        craft()->httpSession->add('oauth.console.providerHandle', $providerHandle);
        craft()->httpSession->remove('oauth.console.token.'.$providerHandle);

        $this->redirect(UrlHelper::getActionUrl('oauth/console/connectStep2'));
    }

    /**
     * Connect Step 2
     *
     * @return null
     */
    public function actionConnectStep2()
    {
        $providerHandle = craft()->httpSession->get('oauth.console.providerHandle');
        $referer = craft()->httpSession->get('oauth.console.referer');


        // connect

        $provider = craft()->oauth->getProvider($providerHandle);

        if($response = craft()->oauth->connect(array(
            'plugin' => 'oauth',
            'provider' => $providerHandle
        )))
        {
            if($response['success'])
            {
                // token
                $token = $response['token'];

                $tokenArray = OauthHelper::tokenToArray($token);

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
     * Disconnect
     *
     * @return null
     */
    public function actionDisconnect()
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