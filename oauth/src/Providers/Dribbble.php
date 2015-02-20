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

class Dribbble extends AbstractProvider {

    public $consoleUrl = 'https://dribbble.com/account/applications';

    public function getName()
    {
        return 'Dribbble';
    }

    public function getUserDetails()
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