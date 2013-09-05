<?php

namespace OAuthProviders;

class GoogleOAuthProvider extends BaseOAuthProvider {

	public $consoleUrl = 'https://code.google.com/apis/console/';
	
	public function getName()
	{
		return 'Google';
	}
}