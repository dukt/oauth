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

namespace OAuthProviderSources;

class InstagramOAuthProviderSource extends BaseOAuthProviderSource {

    public $consoleUrl = 'http://instagram.com/developer/clients/';

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
        try {

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
            // todo
        }
    }
}