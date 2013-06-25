<?php

namespace OAuth\Provider;

use \OAuth\OAuth1\Request\Resource;

class Vimeo extends \OAuth\OAuth1\Provider
{
    public $name = 'vimeo';

    public function requestTokenUrl()
    {
        return 'https://vimeo.com/oauth/request_token';
    }

    public function authorizeUrl()
    {
        return 'https://vimeo.com/oauth/authorize?permission=write';
    }

    public function accessTokenUrl()
    {
        return 'https://vimeo.com/oauth/access_token';
    }

    public function getUserInfo()
    {
        // Create a new GET request with the required parameters
        $request = new Resource('GET', 'http://vimeo.com/api/rest/v2?format=json&method=vimeo.people.getInfo', array(
            'oauth_consumer_key' => $this->consumer->client_id,
            'oauth_token' => $this->token->access_token,
        ));

        // Sign the request using the consumer and token
        $request->sign($this->signature, $this->consumer, $this->token);

        $user = json_decode($request->execute());

        // Create a response from the request
        return array(
            'uid' => $this->token->uid,
            'name' => $user->display_name,
            'location' => $user->location,
            'description' => $user->bio
        );
    }

} // End Provider_Vimeo
