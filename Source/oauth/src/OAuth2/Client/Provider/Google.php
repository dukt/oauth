<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2016, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Dukt\OAuth\OAuth2\Client\Provider;

use League\OAuth2\Client\Entity\User;

class Google extends \League\OAuth2\Client\Provider\Google
{
    // Properties
    // =========================================================================

    public $scopes = [
        'profile',
        'email',
    ];

    // Public Methods
    // =========================================================================

    public function urlUserDetails(\League\OAuth2\Client\Token\AccessToken $token)
    {
        return 'https://www.googleapis.com/oauth2/v1/userinfo';
    }

    public function userDetails($response, \League\OAuth2\Client\Token\AccessToken $token)
    {
        $response = (array) $response;

        $user = new User();

        $uid = $response['id'];
        $name = isset($response['name']) ? $response['name'] : null;
        $firstName = isset($response['given_name']) ? $response['given_name'] : null;
        $lastName = isset($response['family_name']) ? $response['family_name'] : null;
        $email = isset($response['email']) ? $response['email'] : null;
        $imageUrl = isset($response['picture']) ? $response['picture'] : null;

        $user->exchangeArray([
            'uid' => $uid,
            'name' => $name,
            'firstname' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
            'imageUrl' => $imageUrl,
        ]);

        return $user;
    }

    public function userUid($response, \League\OAuth2\Client\Token\AccessToken $token)
    {
        return $response->id;
    }

    public function userEmail($response, \League\OAuth2\Client\Token\AccessToken $token)
    {
        return isset($response->email) ? $response->email : null;
    }

    public function userScreenName($response, \League\OAuth2\Client\Token\AccessToken $token)
    {
        return [$response->given_name, $response->family_name];
    }
}