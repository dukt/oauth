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

class DribbbleOAuthProviderSource extends BaseOAuthProviderSource {

    public $consoleUrl = 'https://dribbble.com/account/applications';

    public function getName()
    {
        return 'Dribbble';
    }

    public function getAccount()
    {
        return array();

        // $response = $this->service->request('/user');

        // $response = json_decode($response, true);

        // $account = array();
        // $account['uid'] = $response['id'];
        // $account['name'] = $response['name'];
        // $account['username'] = $response['username'];
        // $account['email'] = $response['email'];

        // return $account;
    }
}