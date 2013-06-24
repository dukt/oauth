<?php

namespace Dukt\Connect\Facebook;

class Provider extends \OAuth\Provider\Facebook
{
    public $consoleUrl = 'https://code.facebook.com/apis/console/';

    public function getAccount()
    {
        $url = 'https://graph.facebook.com/me?'.http_build_query(array(
            'access_token' => $this->token->access_token,
        ));

        $response = json_decode(file_get_contents($url), true);

        $account = new Account();
        $account->instantiate($response);

        return $account;
    }
}
