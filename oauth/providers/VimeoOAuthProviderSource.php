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

class VimeoOAuthProviderSource extends BaseOAuthProviderSource {

	public $consoleUrl = 'https://developer.vimeo.com/apps';

	public function getName()
	{
		return 'Vimeo';
	}

    public function getUserDetails()
    {
        $url = 'https://api.vimeo.com/me';

        $client = new Client();

        $query = array('access_token' => $this->token->accessToken);

        try {
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
            $data = $e->getResponse()->json();

            throw new \Exception("Couldn’t get account.");
        }
    }

}