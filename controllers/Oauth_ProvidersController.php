<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2016, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Craft;

class Oauth_ProvidersController extends BaseController
{
    // Public Methods
    // =========================================================================

    /**
     * Providers Index
     *
     * @return null
     */
    public function actionIndex()
    {
        $providers = craft()->oauth->getProviders(false);

        $allProviderInfos = [];

        foreach($providers as $provider)
        {
            $allProviderInfos[$provider->getHandle()] = craft()->oauth->getProviderInfos($provider->getHandle());
        }

        $variables['providers'] = $providers;
        $variables['allProviderInfos'] = $allProviderInfos;

        $this->renderTemplate('oauth/providers/_index', $variables);
    }

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

                $configInfos = craft()->config->get('providerInfos', 'oauth');

                if(!empty($configInfos[$variables['handle']]))
                {
                    $variables['configInfos'] = $configInfos[$variables['handle']];
                }

                $this->renderTemplate('oauth/providers/_edit', $variables);
            }
            else
            {
                throw new HttpException(404);
            }
        }
        else
        {
            throw new HttpException(404);
        }
    }

    /**
     * Save provider
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
}