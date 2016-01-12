<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2015, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Craft;

class OauthController extends BaseController
{
    // Properties
    // =========================================================================

    protected $allowAnonymous = array('actionConnect');
    private $handle;
    private $namespace;
    private $scope;
    private $authorizationOptions;
    private $redirect;
    private $referer;

    // Public Methods
    // =========================================================================

    /**
     * Connect
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
     * Connect
     *
     * @return null
     */
    public function actionConnect()
    {
        // OAuth Step 2

        $token = false;
        $success = false;
        $error = false;
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
            $this->scope = craft()->httpSession->get('oauth.scope');
            $this->authorizationOptions = craft()->httpSession->get('oauth.authorizationOptions');
            $this->referer = craft()->httpSession->get('oauth.referer');

            OauthHelper::log('OAuth Connect - Step 2A'."\r\n".print_r([ 'handle' => $this->handle, 'scope' => $this->scope, 'authorizationOptions' => $this->authorizationOptions, 'referer' => $this->referer ], true), LogLevel::Info, true);

            // provider
            $provider = craft()->oauth->getProvider($this->handle);

            // connect
            $tokenResponse = $provider->connect([
                'scope' => $this->scope,
                'authorizationOptions' => $this->authorizationOptions,
            ]);

            // token
            if($tokenResponse)
            {
                $token = OauthHelper::realTokenToArray($tokenResponse);
            }
            else
            {
                throw new Exception("Error with token");
            }

            $success = true;
        }
        catch(\Exception $e)
        {
            $error = true;
            $errorMsg = $e->getMessage();
        }


        // build up response

        $response = array(
            'error' => $error,
            'errorMsg' => $errorMsg,
            'success' => $success,
            'token' => $token,
        );

        OauthHelper::log('OAuth Connect - Step 2B'."\r\n".print_r([ 'response' => $response ], true), LogLevel::Info, true);

        craft()->httpSession->add('oauth.response', $response);

        // redirect
        $this->redirect($this->referer);
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
}