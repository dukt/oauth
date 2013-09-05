<?php

namespace OAuthProviders;

class FacebookOAuthProvider extends BaseOAuthProvider {

	public $consoleUrl = 'https://code.facebook.com/apis/console/';
	
	public function getName()
	{
		return 'Facebook';
	}
}