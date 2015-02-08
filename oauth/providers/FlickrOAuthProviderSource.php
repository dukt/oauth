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

class FlickrOAuthProviderSource extends BaseOAuthProviderSource {

	public $consoleUrl = 'http://www.flickr.com/services/apps/';
    public $oauthVersion = 1;

	public function getName()
	{
		return 'Flickr';
	}
}