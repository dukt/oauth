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

class TwitterOAuthProviderSource extends BaseOAuthProviderSource {

	public $consoleUrl = 'https://dev.twitter.com/apps';
    public $oauthVersion = 1;

	public function getName()
	{
		return 'Twitter';
	}

    public function requestAccessToken($token, $verifier, $secret)
    {
        return $this->service->requestAccessToken($token, $verifier, $secret);
    }

    public function getUserDetails()
    {
        try {

            $response = @$this->service->request('account/verify_credentials.json');

            if(!$response)
            {
                throw new \Exception("Request error", 1);

            }

            $response = json_decode($response, true);

            if(!empty($response['errors']))
            {
                throw new \Exception($response['errors'][0]['message']);
            }

            $account = array();

            $account['uid'] = $response['id'];
            $account['name'] = $response['name'];
            $account['username'] = $response['screen_name'];

            return $account;
        }
        catch(\Exception $e)
        {
            // todo
        }
    }
}