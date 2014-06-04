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

class GoogleOAuthProviderSource extends BaseOAuthProviderSource {

	public $consoleUrl = 'https://code.google.com/apis/console/';

	public function getName()
	{
		return 'Google';
	}

    public function getAccount()
    {
        $response = $this->service->request('https://www.googleapis.com/oauth2/v1/userinfo');
        $response = json_decode($response, true);

        $account = array();

        $account['uid'] = $response['id'];
        $account['name'] = $response['name'];
        $account['email'] = $response['email'];

        return $account;
    }
}