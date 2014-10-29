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

    public $consoleUrl = 'https://developers.facebook.com/apps';

    public function getName()
    {
        return 'Dribbble';
    }

    public function getAccount()
    {
        $response = $this->service->request('/user');
        var_dump($response);
        die();
        // $response = json_decode($response, true);

        // $account = array();
        // $account['uid'] = $response['id'];
        // $account['name'] = $response['name'];
        // $account['username'] = $response['username'];
        // $account['email'] = $response['email'];

        return $account;
    }
}