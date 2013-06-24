<?php

namespace Dukt\Connect\Google;

class Provider extends \OAuth\Provider\Google
{
    public $consoleUrl = 'https://code.google.com/apis/console/';

    function getAccount()
    {
        $url = 'https://www.googleapis.com/oauth2/v1/userinfo?alt=json&'.http_build_query(array(
            'access_token' => $this->token->access_token,
        ));

        $response = json_decode(file_get_contents($url), true);

        $account = new Account();
        $account->instantiate($response);

        return $account;

    }
}
