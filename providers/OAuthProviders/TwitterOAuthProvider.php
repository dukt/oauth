<?php

namespace OAuthProviders;

class TwitterOAuthProvider extends BaseOAuthProvider {

	public $consoleUrl = 'https://dev.twitter.com/apps';
	
	public function getName()
	{
		return 'Twitter';
	}
}