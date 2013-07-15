<?php

namespace Dukt\Connect\Twitter;

use Dukt\Connect\Common\AbstractAccount;

class Account extends AbstractAccount
{

    public function instantiate($response)
    {
    	$response = json_decode($response);
    	// var_dump($response->screen_name);
    	// die('yeah');
        $this->email = $response->screen_name.'@oauth.twitter';
        $this->mapping = $response->screen_name.'@oauth.twitter';
    }
}
