<?php

namespace Dukt\Connect\Twitter;

use \OAuth\OAuth1\Token;
use \OAuth\OAuth1\Token\Access;
use \OAuth\OAuth1\Consumer;
use \OAuth\OAuth1\Request\Resource;
use \Exception;


class Provider extends \OAuth\Provider\Twitter
{
    public $consoleUrl = 'https://dev.twitter.com/apps';

    public function getAccount()
    {
        if (! $this->token instanceof Access) {
        //if (! is_object($this->token)) {
            throw new Exception('Token must be an instance of Access');
        }

        // Create a new GET request with the required parameters
        $request = new Resource('GET', 'https://api.twitter.com/1.1/account/settings.json', array(
            'oauth_consumer_key' => $this->consumer->client_id,
            'oauth_token' => $this->token->access_token,
            'user_id' => $this->token->uid,
        ));

        // Sign the request using the consumer and token
        $request->sign($this->signature, $this->consumer, $this->token);

        $response = $request->execute();

        // // Create a response from the request
        // $response = array(
        //     'uid' => $this->token->uid,
        //     'nickname' => $user->screen_name,
        //     'name' => $user->name ? $user->name : $user->screen_name,
        //     'location' => $user->location,
        //     'image' => $user->profile_image_url,
        //     'description' => $user->description,
        //     'urls' => array(
        //       'Website' => $user->url,
        //       'Twitter' => 'http://twitter.com/'.$user->screen_name,
        //     ),
        // );

        $account = new Account();
        $account->instantiate($response);

        return $account;
    }
}
