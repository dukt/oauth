<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2016, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Craft;

class Oauth_TokensController extends BaseController
{
    // Public Methods
    // =========================================================================

    /**
     * Tokens Index
     *
     * @return null
     */
    public function actionIndex()
    {
        $variables['tokens'] = craft()->oauth->getTokens();
        $this->renderTemplate('oauth/tokens/_index', $variables);
    }

    /**
     * Provider Tokens
     *
     * @return null
     */
    public function actionProviderTokens(array $variables = [])
    {
        $variables['provider'] = craft()->oauth->getProvider($variables['handle']);

        if($variables['provider'])
        {
            $this->renderTemplate('oauth/providers/_tokens', $variables);
        }
        else
        {
            throw new HttpException(404);
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