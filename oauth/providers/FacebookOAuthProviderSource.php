<?php

namespace OAuthProviderSources;

class FacebookOAuthProviderSource extends BaseOAuthProviderSource {

	public $consoleUrl = 'https://code.facebook.com/apis/console/';
	
	public function getName()
	{
		return 'Facebook';
	}
}