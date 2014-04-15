<?php

/**
 * Craft OAuth by Dukt
 *
 * @package   Craft OAuth
 * @author    Benjamin David
 * @copyright Copyright (c) 2014, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 * @link      https://dukt.net/craft/oauth/
 */

namespace OAuthProviderSources;

class InstagramOAuthProviderSource extends BaseOAuthProviderSource {

	public $consoleUrl = 'https://api.instagram.com/oauth/authorize';

	public function getName()
	{
		return 'Instagram';
	}
}
