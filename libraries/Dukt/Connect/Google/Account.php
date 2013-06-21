<?php

namespace Dukt\Connect\Google;

use Dukt\Connect\Common\AbstractAccount;

class Account extends AbstractAccount
{

    public function instantiate($response)
    {
        $this->email = $response['email'];
    }
}
