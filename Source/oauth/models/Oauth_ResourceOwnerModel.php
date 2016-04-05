<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2016, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Craft;

class Oauth_ResourceOwnerModel extends BaseModel
{
	// Public Methods
	// =========================================================================

	public function getUid()
	{
		return $this->id;
	}
	
	// Protected Methods
	// =========================================================================

	/**
	 * Define Attributes
	 */
	protected function defineAttributes()
	{
		$attributes = array(
			'id' => AttributeType::Number,
			'email' => AttributeType::String,
			'name' => AttributeType::String,
		);

		return $attributes;
	}

}
