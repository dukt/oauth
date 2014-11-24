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

class RunKeeperOAuthProviderSource extends BaseOAuthProviderSource {

    public $consoleUrl = 'http://runkeeper.com/partner/applications';

    public function getName()
    {
        return 'RunKeeper';
    }

    public function getAccount()
    {
        return array();
        // $response = $this->service->request('/user');
        // $response = json_decode($response, true);

        // $account = array();

        // $account['uid'] = $response['id'];
        // $account['name'] = $response['name'];
        // $account['username'] = $response['login'];
        // $account['email'] = $response['email'];

        // return $account;
    }
}