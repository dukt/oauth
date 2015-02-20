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

class Github extends AbstractProvider {

	public $consoleUrl = 'https://github.com/settings/applications/';
    public $oauthVersion = 2;

	public function getName()
	{
		return 'GitHub';
	}

    public function getUserDetails()
    {
        // $response = $this->service->request('user');
        // $response = json_decode($response, true);

        // $account = array();

        // $account['uid'] = $response['id'];
        // $account['name'] = $response['name'];
        // $account['username'] = $response['login'];
        // $account['email'] = $response['email'];

        // return $account;


        $url = 'https://api.github.com/user';

        $client = new Client();

        $query = array('access_token' => $this->token->accessToken);

        try {
            $guzzleRequest = $client->get($url, null, array('query' => $query));
            $response = $guzzleRequest->send();
            $data = $response->json();

            return array(
                'uid' => $data['id'],
                'name' => $data['name'],
                'username' => $data['login'],
                'email' => $data['email'],
            );
        }
        catch(\Exception $e)
        {
            $data = $e->getResponse()->json();

            // throw new \Exception("Couldn’t get account.");

            return false;
        }
    }
}