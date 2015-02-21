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

class Vimeo extends AbstractProvider {

	public $consoleUrl = 'https://developer.vimeo.com/apps';
    public $oauthVersion = 2;

	public function getName()
	{
		return 'Vimeo';
	}

    public function getUserDetails()
    {
        $url = 'https://api.vimeo.com/me';

        $client = new Client();

        $query = array('access_token' => $this->token->accessToken);

        try
        {
            $guzzleRequest = $client->get($url, null, array('query' => $query));
            $response = $guzzleRequest->send();
            $data = $response->json();


            $account = array();

            $account['uid'] = substr($data['uri'], strrpos($data['uri'], "/") + 1);
            $account['name'] = $data['name'];

            return $account;
        }
        catch(\Exception $e)
        {
            return false;
        }
    }

}