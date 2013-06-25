<?php

namespace Dukt\Connect\Github;

class Provider extends \OAuth\Provider\Github
{
    public $consoleUrl = 'https://github.com/settings/applications/';

    public function __construct(array $options = array())
    {
        if(!isset($options['scope']))
        {
            $options['scope'] = array(
                'repo',
                'user'
            );
        }

        parent::__construct($options);
    }

    function getAccount()
    {
        $url = 'https://api.github.com/user?'.http_build_query(array(
            'access_token' => $this->token->access_token,
        ));

        $response = json_decode(file_get_contents($url), true);

        $account = new Account();
        $account->instantiate($response);

        return $account;

    }
}
