<?php

namespace OAuthProviderSources;

class GoogleOAuthProviderSource extends BaseOAuthProviderSource {

	public $consoleUrl = 'https://code.google.com/apis/console/';
	
	public function getName()
	{
		return 'Google';
	}
}