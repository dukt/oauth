<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2015, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Dukt\OAuth\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Entity\User;

class Slack extends AbstractProvider
{
    // Public Methods
    // =========================================================================

    public function urlAuthorize()
    {
        return 'https://slack.com/oauth/authorize';
    }

    public function urlAccessToken()
    {
        return 'https://slack.com/api/oauth.access';
    }

    public function urlUserDetails(\League\OAuth2\Client\Token\AccessToken $token)
    {
        return 'https://slack.com/api/api.test?access_token='.$token;
    }

    public function userDetails($response, \League\OAuth2\Client\Token\AccessToken $token)
    {
        var_dump($response);

        $user = new User;
        $user->uid = substr($response->uri, strrpos($response->uri, "/") + 1);
        $user->name = $response->name;
        return $user;
    }
}