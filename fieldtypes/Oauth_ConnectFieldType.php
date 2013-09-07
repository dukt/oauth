<?php


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

	// --------------------------------------------------------------------

	/**
	 * Show field
	 */
	public function getInputHtml($name, $value)
	{
		return craft()->templates->render('oauth/fields/connect', array(
			'element' => $this->element
		));
	}

	// --------------------------------------------------------------------
}
