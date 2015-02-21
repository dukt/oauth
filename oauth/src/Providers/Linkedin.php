<?php

/**
 * Craft OAuth by Dukt
 *
 * @package   Craft OAuth
 * @author    Benjamin David
 * @copyright Copyright (c) 2015, Dukt
 * @link      https://dukt.net/craft/oauth/
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Dukt\OAuth\Providers;

class Linkedin extends AbstractProvider {

    public $consoleUrl = 'https://www.linkedin.com/secure/developer';
    public $oauthVersion = 2;

    public function getName()
    {
        return 'LinkedIn';
    }

    public function getUserDetails()
    {
        try
        {
            $response = $this->service->request('/people/~?format=json');
            $response = json_decode($response, true);

            $account = array();

            $account['uid'] = $response['id'];
            $account['name'] = trim($response['firstName']." ".$response['lastName']);

            return $account;
        }
        catch(\Exception $e)
        {
            return false;
        }
    }

    public function getAuthorizationMethod()
    {
        return 'oauth2_access_token';
    }
}