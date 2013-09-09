<?php

namespace OAuthProviderSources;

class GithubOAuthProviderSource extends BaseOAuthProviderSource {

	public $consoleUrl = 'https://github.com/settings/applications/';

	public function getName()
	{
		return 'GitHub';
	}
}