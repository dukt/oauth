<?php

namespace OAuthProviderSources;

class TwitterOAuthProviderSource extends BaseOAuthProviderSource {

	public $consoleUrl = 'https://dev.twitter.com/apps';
	
	public function getName()
	{
		return 'Twitter';
	}
}