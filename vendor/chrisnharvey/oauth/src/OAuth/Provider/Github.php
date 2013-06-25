<?php

namespace OAuth\Provider;

use \OAuth\OAuth2\Token\Access;

/**
 * GitHub OAuth2 Provider
 *
 * @package    CodeIgniter/OAuth2
 * @category   Provider
 * @author     Phil Sturgeon
 * @copyright  (c) 2012 HappyNinjas Ltd
 * @license    http://philsturgeon.co.uk/code/dbad-license
 */

class Github extends \OAuth\OAuth2\Provider
{
    public function authorizeUrl()
    {
        return 'https://github.com/login/oauth/authorize';
    }

    public function accessTokenUrl()
    {
        return 'https://github.com/login/oauth/access_token';
    }

    public function getUserInfo()
    {
        $url = 'https://api.github.com/user?'.http_build_query(array(
            'access_token' => $this->token->access_token,
        ));

        $user = json_decode(file_get_contents($url));

        // Create a response from the request
        return array(
            'uid' => $user->id,
            'nickname' => $user->login,
            'name' => $user->name,
            'email' => $user->email,
            'urls' => array(
              'GitHub' => 'http://github.com/'.$user->login,
              'Blog' => $user->blog,
            ),
        );
    }
}
