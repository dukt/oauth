<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2015, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Craft;

class Oauth_TokensController extends BaseController
{
    // Properties
    // =========================================================================

    protected $allowAnonymous = array('actionConnect');
    private $handle;
    private $namespace;
    private $scope;
    private $redirect;
    private $referer;

    // Public Methods
    // =========================================================================

    /**
     * Connect
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
}