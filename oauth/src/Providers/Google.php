<?php

/**
 * Craft OAuth by Dukt
 *
 * @package   Craft OAuth
 * @author    Benjamin David
 * @copyright Copyright (c) 2015, Dukt
 * @link      https://dukt.net/craft/oauth/
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Dukt\OAuth\Providers;

use Guzzle\Http\Client;

class Google extends AbstractProvider {

	public $consoleUrl = 'https://code.google.com/apis/console/';
    public $oauthVersion = 2;

    protected $scopes = array(
        'userinfo_profile',
        'userinfo_email'
    );

	public function getName()
	{
		return 'Google';
	}

    public function getParams()
    {
        return  array(
            'access_type' => 'offline',
            'approval_prompt' => 'force'
        );
    }

    public function getUserDetails()
    {
        $url = 'https://www.googleapis.com/oauth2/v1/userinfo';

        $client = new Client();

        $query = array('access_token' => $this->token->accessToken);

        try
        {
            $guzzleRequest = $client->get($url, null, array('query' => $query));
            $response = $guzzleRequest->send();
            $data = $response->json();

            return array(
                'uid' => $data['id'],
                'name' => $data['name'],
                'email' => $data['email'],
            );
        }
        catch(\Exception $e)
        {
            return false;
        }
    }

}