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

class GithubOAuthProviderSource extends BaseOAuthProviderSource {

	public $consoleUrl = 'https://github.com/settings/applications/';
    public $oauthVersion = 2;

	public function getName()
	{
		return 'GitHub';
	}

    public function getUserDetails()
    {
        $response = $this->service->request('user');
        $response = json_decode($response, true);

        $account = array();

        $account['uid'] = $response['id'];
        $account['name'] = $response['name'];
        $account['username'] = $response['login'];
        $account['email'] = $response['email'];

        return $account;
    }
}