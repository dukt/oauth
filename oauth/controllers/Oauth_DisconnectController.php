<?php

namespace Craft;

require(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');

class Oauth_DisconnectController extends BaseController
{
    protected $allowAnonymous = true;

    // --------------------------------------------------------------------

    public function init()
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        // request params

        $params = array();
        $params['provider'] = craft()->request->getParam('provider');
        $params['namespace'] = craft()->request->getParam('namespace');


        // clean session vars

        craft()->oauth->sessionClean();

        craft()->oauth->sessionAdd('oauth.referer', $_SERVER['HTTP_REFERER']);


        // redirect

        $url = UrlHelper::getActionUrl('oauth/public/disconnect/', $params);

        $this->redirect($url);
    }

    // --------------------------------------------------------------------
}