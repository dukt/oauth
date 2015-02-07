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

namespace OAuthProviderSources;

use Guzzle\Http\Client;

class FacebookOAuthProviderSource extends BaseOAuthProviderSource {

	public $consoleUrl = 'https://developers.facebook.com/apps';
    public $oauthVersion = 2;

	public function getName()
	{
		return 'Facebook';
	}

    public function getUserDetails()
    {
        // $response = $this->service->request('/me');
        // $response = json_decode($response, true);

        // $account = array();

        // $account['uid'] = $response['id'];

        // if(!empty($response['name']))
        // {
        //     $account['name'] = $response['name'];
        // }

        // if(!empty($response['username']))
        // {
        //     $account['username'] = $response['username'];
        // }

        // if(!empty($response['email']))
        // {
        //     $account['email'] = $response['email'];
        // }

        // return $account;





        $url = 'https://graph.facebook.com/me';

        $client = new Client();

        $query = array('access_token' => $this->token->accessToken);

        try {
            $guzzleRequest = $client->get($url, null, array('query' => $query));
            $response = $guzzleRequest->send();
            $data = $response->json();

            $account = array();

            $account['uid'] = $data['id'];

            if(!empty($data['name']))
            {
                $account['name'] = $data['name'];
            }

            if(!empty($data['username']))
            {
                $account['username'] = $data['username'];
            }

            if(!empty($data['email']))
            {
                $account['email'] = $data['email'];
            }

            return $account;
        }
        catch(\Exception $e)
        {
            $data = $e->getResponse()->json();

            // throw new \Exception("Couldn’t get account.");

            return false;
        }
    }
}