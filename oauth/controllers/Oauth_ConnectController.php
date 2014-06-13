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

class Oauth_ConnectController extends BaseController
{
    protected $allowAnonymous = true;

    public function init()
    {

        $handle    = craft()->request->getParam('provider');
        $namespace = craft()->request->getParam('namespace');


        // scopes
        $scopes = craft()->request->getParam('scopes');
        $scopes = unserialize(base64_decode($scopes));

        // params
        $params = craft()->request->getParam('params');
        $params = unserialize(base64_decode($params));


        // session vars

        craft()->oauth->sessionClean();

        craft()->httpSession->add('oauth.referer', (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null));
        craft()->httpSession->add('oauth.scopes', $scopes);
        craft()->httpSession->add('oauth.namespace', $namespace);
        craft()->httpSession->add('oauth.params', $params);


        // redirect
        $redirect = UrlHelper::getActionUrl('oauth/public/connect/', array('provider' => $handle));
        $this->redirect($redirect);
    }
}

