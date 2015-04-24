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

class Flickr extends AbstractProvider {

	public $consoleUrl = 'http://www.flickr.com/services/apps/';
    public $oauthVersion = 1;

    public function requestAccessToken($token, $verifier, $secret)
    {
        return $this->service->requestAccessToken($token, $verifier, $secret);
    }

	public function getName()
	{
		return 'Flickr';
	}

    public function getUserDetails()
    {
        try
        {
            $xml = simplexml_load_string($this->service->request('flickr.test.login'));

            $account = array();
            $account['uid'] = (string) $xml->user->attributes()->id;
            $account['username'] = (string) $xml->user->username;

            return $account;
        }
        catch(\Exception $e)
        {
            return false;
        }
    }
}