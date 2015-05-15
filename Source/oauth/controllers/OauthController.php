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
    private $scopes;
    private $params;
    private $redirect;
    private $referer;

    // Public Methods
    // =========================================================================

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

            if(is_array($this->scopes))
            {
                $provider->setScopes($this->scopes);
            }


            // init service

            switch($provider->oauthVersion)
            {
                case 2:

                    if (!isset($_GET['code']))
                    {
                        $authUrl = $provider->getAuthorizationUrl($this->params);
                        $_SESSION['oauth2state'] = $provider->getProvider()->state;
                        header('Location: '.$authUrl);
                        exit;
                    }
                    elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state']))
                    {
                        unset($_SESSION['oauth2state']);

                        throw new Exception("Invalid state");

                    }
                    else
                    {
                        $token = $provider->getProvider()->getAccessToken('authorization_code', [
                            'code' => $_GET['code']
                        ]);
                    }

                    break;

                case 1:

                    if (isset($_GET['user']))
                    {
                        if ( ! isset($_SESSION['token_credentials']))
                        {
                            throw new Exception("Token credentials not provided");
                        }

                        $token = unserialize($_SESSION['token_credentials']);
                    }
                    elseif (isset($_GET['oauth_token']) && isset($_GET['oauth_verifier']))
                    {
                        $temporaryCredentials = unserialize($_SESSION['temporary_credentials']);

                        $token = $provider->getProvider()->getTokenCredentials($temporaryCredentials, $_GET['oauth_token'], $_GET['oauth_verifier']);

                        unset($_SESSION['temporary_credentials']);

                        $_SESSION['token_credentials'] = serialize($token);
                    }
                    elseif (isset($_GET['denied']))
                    {
                        throw new Exception("Client access denied by the user");
                    }
                    else
                    {
                        $temporaryCredentials = $provider->getProvider()->getTemporaryCredentials();
                        $_SESSION['temporary_credentials'] = serialize($temporaryCredentials);
                        $provider->getProvider()->authorize($temporaryCredentials);
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

                $configInfos = craft()->config->get('oauth');

                if(!empty($configInfos[$variables['handle']]))
                {
                    $variables['configInfos'] = $configInfos[$variables['handle']];
                }

                $this->renderTemplate('oauth/_provider', $variables);
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
}