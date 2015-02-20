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

class RunKeeper extends AbstractProvider {

    public $consoleUrl = 'http://runkeeper.com/partner/applications';
    public $oauthVersion = 2;

    public function getName()
    {
        return 'RunKeeper';
    }

    public function getUserDetails()
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