<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2016, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Craft;

class Oauth_ResourceOwnerModel extends BaseModel
{
	private $sourceOwner;

	// Public Methods
	// =========================================================================

	/**
	 * Define Attributes
	 */
	protected function defineAttributes()
	{
		$attributes = array(
			'id' => AttributeType::Number,
			'email' => AttributeType::String,
			'firstName' => AttributeType::String,
			'lastName' => AttributeType::String,
			'name' => AttributeType::String,
		);

		return $attributes;
	}

	public function getUid()
	{
		return $this->id;
	}
}
