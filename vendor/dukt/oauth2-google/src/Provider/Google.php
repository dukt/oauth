<?php

namespace Dukt\OAuth2\Client\Provider;

use League\OAuth2\Client\Token\AccessToken;

class Google extends \League\OAuth2\Client\Provider\Google
{
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return 'https://www.googleapis.com/oauth2/v1/userinfo';
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new GoogleUser($response);
    }
}
