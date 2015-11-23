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
            $this->scope = craft()->httpSession->get('oauth.scope');
            $this->authorizationOptions = craft()->httpSession->get('oauth.authorizationOptions');
            $this->referer = craft()->httpSession->get('oauth.referer');

            OauthHelper::log('OAuth Connect - Step 2A'."\r\n".print_r([
                    'handle' => $this->handle,
                    'scope' => $this->scope,
                    'authorizationOptions' => $this->authorizationOptions,
                    'referer' => $this->referer,
                ], true), LogLevel::Info, true);

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

            // source oauth provider
            $oauthProvider = $provider->getProvider();


            // init service

            switch($provider->getOauthVersion())
            {
                case 2:
                    $state = craft()->request->getParam('state');
                    $code = craft()->request->getParam('code');
                    $oauth2state = craft()->httpSession->get('oauth2state');

                    if (is_null($code))
                    {
                        OauthHelper::log('OAuth 2 Connect - Step 1', LogLevel::Info);

                        $oauthProvider->setScopes($this->scope);

                        $options = $this->authorizationOptions;

                        if(!empty($this->authorizationOptions['access_type']) && $this->authorizationOptions['access_type'] == 'offline')
                        {
                            unset($this->authorizationOptions['access_type']);
                            $oauthProvider->setAccessType('offline');
                        }

                        $authorizationUrl = $oauthProvider->getAuthorizationUrl($options);

                        craft()->httpSession->add('oauth2state', $oauthProvider->state);

                        OauthHelper::log('OAuth 2 Connect - Step 1 - Data'."\r\n".print_r([
                            'authorizationUrl' => $authorizationUrl,
                            'oauth2state' => craft()->httpSession->get('oauth2state')
                        ], true), LogLevel::Info);

                        craft()->request->redirect($authorizationUrl);
                    }
                    elseif (!$state || $state !== $oauth2state)
                    {
                        OauthHelper::log('OAuth 2 Connect - Step 1.5'."\r\n".print_r([
                            'error' => "Invalid state",
                            'state' => $state,
                            'oauth2state' => $oauth2state,
                        ], true), LogLevel::Info, true);

                        craft()->httpSession->remove('oauth2state');

                        throw new Exception("Invalid state");

                    }
                    else
                    {
                        OauthHelper::log('OAuth 2 Connect - Step 2', LogLevel::Info, true);

                        $token = $oauthProvider->getAccessToken('authorization_code', [
                            'code' => $code
                        ]);

                        OauthHelper::log('OAuth 2 Connect - Step 2 - Data'."\r\n".print_r([
                            'code' => $code,
                            'token' => $token,
                        ], true), LogLevel::Info, true);
                    }

                    break;

                case 1:

                    $user = craft()->request->getParam('user');
                    $oauth_token = craft()->request->getParam('oauth_token');
                    $oauth_verifier = craft()->request->getParam('oauth_verifier');
                    $denied = craft()->request->getParam('denied');

                    // if(isset($_GET['user']))
                    // {
                    //     echo "user exists !";
                    // }
                    // if ($user)
                    // {
                    //     OauthHelper::log('OAuth 1 Connect - Step 3', LogLevel::Info, true);

                    //     if (!craft()->httpSession->get('token_credentials'))
                    //     {
                    //         throw new Exception("Token credentials not provided");
                    //     }

                    //     $token = unserialize(craft()->httpSession->get('oauth2state'));
                    // }
                    // else

                    if ($oauth_token && $oauth_verifier)
                    {
                        OauthHelper::log('OAuth 1 Connect - Step 2', LogLevel::Info, true);

                        $temporaryCredentials = unserialize(craft()->httpSession->get('temporary_credentials'));

                        $token = $oauthProvider->getTokenCredentials($temporaryCredentials, $oauth_token, $oauth_verifier);

                        craft()->httpSession->add('token_credentials', serialize($token));

                        OauthHelper::log('OAuth 1 Connect - Step 2 - Data'."\r\n".print_r([
                            'temporaryCredentials' => $temporaryCredentials,
                            'oauth_token' => $oauth_token,
                            'oauth_verifier' => $oauth_verifier,
                            'token' => $token,
                        ], true), LogLevel::Info, true);
                    }
                    elseif ($denied)
                    {
                        OauthHelper::log('OAuth 1 Connect - Step 1.5'."\r\n".print_r(["Client access denied by the user"], true), LogLevel::Info, true);

                        throw new Exception("Client access denied by the user");
                    }
                    else
                    {
                        OauthHelper::log('OAuth 1 Connect - Step 1', LogLevel::Info, true);

                        $temporaryCredentials = $oauthProvider->getTemporaryCredentials();

                        craft()->httpSession->add('temporary_credentials', serialize($temporaryCredentials));

                        $authorizationUrl = $oauthProvider->getAuthorizationUrl($temporaryCredentials);
                        craft()->request->redirect($authorizationUrl);

                        OauthHelper::log('OAuth 1 Connect - Step 1 - Data'."\r\n".print_r([
                            'temporaryCredentials' => $temporaryCredentials,
                            'authorizationUrl' => $authorizationUrl,
                        ], true), LogLevel::Info, true);
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
            $tokenArray = OauthHelper::realTokenToArray($token);


        }

        if(!is_array($tokenArray))
        {
            throw new Exception("Error with token");
        }

        $response = array(
            'error' => $error,
            'errorMsg' => $errorMsg,
            'success' => $success,
            'token' => $tokenArray,
        );

        OauthHelper::log('OAuth Connect - Step 2B'."\r\n".print_r([
                'response' => $response
            ], true), LogLevel::Info, true);

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