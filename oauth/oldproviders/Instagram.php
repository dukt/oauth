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

class Instagram extends AbstractProvider {

    public $consoleUrl = 'http://instagram.com/developer/clients/';
    public $oauthVersion = 2;

    public function getName()
    {
        return 'Instagram';
    }

    public function getScopes()
    {
        return array('basic');
    }

    public function getUserDetails()
    {
        try
        {
            $response = $this->service->request('users/self');
            $response = json_decode($response, true);

            $account = array();

            $account['uid'] = $response['data']['id'];
            $account['name'] = $response['data']['full_name'];
            $account['username'] = $response['data']['username'];

            return $account;
        }
        catch(\Exception $e)
        {
            return false;
        }
    }
}