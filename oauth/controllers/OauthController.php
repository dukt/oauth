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

class OauthController extends BaseController
{
    /**
     * Save Provider
     */
    public function actionProviderSave()
    {
        $handle = craft()->request->getParam('providerHandle');
        $attributes = craft()->request->getPost('provider');

        $provider = craft()->oauth->getProvider($handle, false);
        $provider->setAttributes($attributes);

        if (craft()->oauth->providerSave($provider))
        {
            craft()->userSession->setNotice(Craft::t('Service saved.'));
            $redirect = craft()->request->getPost('redirect');
            $this->redirect($redirect);
        }
        else
        {
            craft()->userSession->setError(Craft::t("Couldn't save service."));
            craft()->urlManager->setRouteVariables(array('service' => $provider));
        }
    }

    /**
     * Test Connect
     */
    public function actionConnect()
    {
        $handle = craft()->request->getParam('provider');

        $provider = craft()->oauth->getProvider($handle);

        $scopes = $provider->source->getScopes();
        $params = $provider->source->getParams();

        if($response = craft()->oauth->connect(array(
            'plugin' => 'oauth',
            'provider' => $handle,
            'scopes' => $scopes,
            'params' => $params
        )))
        {
            if($response['success'])
            {
                // token
                $token = $response['token'];

                // save token
                craft()->oauth->saveToken($handle, $token);

                // session notice
                craft()->userSession->setNotice(Craft::t("Connected."));
            }
            else
            {
                craft()->userSession->setError(Craft::t($response['errorMsg']));
            }

            $this->redirect($response['redirect']);
        }
    }

    /**
     * Test Disconnect
     */
    public function actionDisconnect()
    {
        $handle = craft()->request->getParam('provider');

        // reset token
        craft()->oauth->saveToken($handle, null);

        // set notice
        craft()->userSession->setNotice(Craft::t("Disconnected."));

        // redirect
        $redirect = craft()->request->getUrlReferrer();
        $this->redirect($redirect);
    }
}

