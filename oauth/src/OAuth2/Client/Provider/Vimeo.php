<?php

namespace Dukt\OAuth\OAuth2\Client\Provider;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Entity\User;

class Vimeo extends AbstractProvider
{
    public function urlAuthorize()
    {
        return 'https://api.vimeo.com/oauth/authorize';
    }

    public function urlAccessToken()
    {
        return 'https://api.vimeo.com/oauth/access_token';
    }

    public function urlUserDetails(\League\OAuth2\Client\Token\AccessToken $token)
    {
        return 'https://api.vimeo.com/me?access_token='.$token;
    }

    public function userDetails($response, \League\OAuth2\Client\Token\AccessToken $token)
    {
        $user = new User;
        $user->uid = substr($response->uri, strrpos($response->uri, "/") + 1);
        $user->name = $response->name;
        return $user;
    }
}