<?php

namespace Dukt\Connect\Github;

use Dukt\Connect\Common\AbstractAccount;

class Account extends AbstractAccount
{

    public function instantiate($response)
    {
        $this->email = $response['email'];
        $this->mapping = $response['email'];
    }
}
