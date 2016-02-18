<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2016, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Craft;

class OauthController extends BaseController
{
    // Properties
    // =========================================================================

    protected $allowAnonymous = array('actionConnect');

    // Public Methods
    // =========================================================================

    /**
     * Connect
     *
     * @return null
     */
    public function actionConnect()
    {
        $token = false;
        $success = false;
        $error = false;
        $errorMsg = false;

        // handle
        $providerHandle = craft()->httpSession->get('oauth.handle');

        if(!$providerHandle)
        {
            $providerHandle = craft()->request->getParam('provider');

            if($providerHandle)
            {
                craft()->httpSession->add('oauth.handle', $providerHandle);
            }
            else
            {
                throw new Exception("Couldn’t retrieve OAuth provider.");
            }
        }

        // session vars
        $scope = craft()->httpSession->get('oauth.scope');
        $authorizationOptions = craft()->httpSession->get('oauth.authorizationOptions');
        $referer = craft()->httpSession->get('oauth.referer');

        OauthPlugin::log('OAuth Connect - Step 2A'."\r\n".print_r([ 'handle' => $providerHandle, 'scope' => $scope, 'authorizationOptions' => $authorizationOptions, 'referer' => $referer ], true), LogLevel::Info, true);


        try
        {
            // provider
            $provider = craft()->oauth->getProvider($providerHandle);

            // connect
            $tokenResponse = $provider->connect([
                'scope' => $scope,
                'authorizationOptions' => $authorizationOptions,
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

        OauthPlugin::log('OAuth Connect - Step 2B'."\r\n".print_r([ 'response' => $response ], true), LogLevel::Info, true);

        craft()->httpSession->add('oauth.response', $response);

        // redirect
        $this->redirect($referer);
    }
}