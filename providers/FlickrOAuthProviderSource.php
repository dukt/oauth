<?php

namespace OAuthProviderSources;

class FlickrOAuthProviderSource extends BaseOAuthProviderSource {

	public $consoleUrl = 'http://www.flickr.com/services/apps/';
	
	public function getName()
	{
		return 'Flickr';
	}
}