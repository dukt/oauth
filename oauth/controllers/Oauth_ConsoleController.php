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

class Oauth_ConsoleController extends BaseController
{
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