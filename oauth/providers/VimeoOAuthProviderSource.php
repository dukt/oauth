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

class VimeoOAuthProviderSource extends BaseOAuthProviderSource {

	public $consoleUrl = 'https://developer.vimeo.com/apps';

	public function getName()
	{
		return 'Vimeo';
	}

    public function getAccount()
    {
        try {

            $response = $this->service->request('/me');
            $response = json_decode($response, true);

            $account = array();

            $account['uid'] = substr($response['uri'], strrpos($response['uri'], "/") + 1);
            $account['name'] = $response['name'];

            return $account;
        }
        catch(\Exception $e)
        {
            // todo
            throw new \Exception($e, 1);

        }
    }
}