<?php

/**
 * Craft OAuth by Dukt
 *
 * @package   Craft OAuth
 * @author    Benjamin David
 * @copyright Copyright (c) 2013, Dukt
 * @license   http://dukt.net/craft/oauth/docs/license
 * @link      http://dukt.net/craft/oauth/
 */

namespace Craft;

class Oauth_ConnectFieldType extends BaseFieldType
{
	/**
	 * Block type name
	 */
	public function getName()
	{
		return Craft::t('OAuth Connect');
	}

	/**
	 * Show field
	 */
	public function getInputHtml($name, $value)
	{
		return craft()->templates->render('oauth/fields/connect', array(
			'element' => $this->element
		));
	}
}
