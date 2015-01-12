<?php

/**
 * Craft OAuth by Dukt
 *
 * @package   Craft OAuth
 * @author    Benjamin David
 * @copyright Copyright (c) 2014, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 * @link      https://dukt.net/craft/oauth/
 */

namespace OAuthProviderSources;

use Guzzle\Http\Client;

class GoogleOAuthProviderSource extends BaseOAuthProviderSource {

	public $consoleUrl = 'https://code.google.com/apis/console/';

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

        $query = array('access_token' => $this->token->getAccessToken());

        try {
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
            $data = $e->getResponse()->json();

            throw new \Exception("Couldn’t get account.");
        }
    }

}