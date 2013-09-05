<?php

namespace OAuthProviders;

class GithubOAuthProvider extends BaseOAuthProvider {

	public $consoleUrl = 'https://github.com/settings/applications/';

	public function getName()
	{
		return 'GitHub';
	}
}