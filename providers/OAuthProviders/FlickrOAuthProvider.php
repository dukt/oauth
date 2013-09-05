<?php

namespace OAuthProviders;

class FlickrOAuthProvider extends BaseOAuthProvider {

	public $consoleUrl = 'http://www.flickr.com/services/apps/';
	
	public function getName()
	{
		return 'Flickr';
	}
}