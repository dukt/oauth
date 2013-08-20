<?php

namespace Craft;

require(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');

class Oauth_DisconnectController extends BaseController
{
    protected $allowAnonymous = true;

    // --------------------------------------------------------------------

    public function init()
    {
        // request params

        $params = array();
        $params['provider'] = craft()->request->getParam('provider');
        $params['namespace'] = craft()->request->getParam('namespace');


        // clean session vars

        craft()->oauth->httpSessionClean();

        craft()->oauth->httpSessionAdd('oauth.referer', $_SERVER['HTTP_REFERER']);


        // redirect

        $url = UrlHelper::getActionUrl('oauth/public/disconnect/', $params);

        $this->redirect($url);
    }
}