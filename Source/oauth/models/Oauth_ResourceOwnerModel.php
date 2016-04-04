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

	//	public function getId() {}
	//	public function getName() {}
	//	public function getFirstName() {}
	//	public function getLastName() {}
	//	public function getAvatar() {}
	//	public function getBio() {}
	//	public function getCoverPhotoUrl() {}
	//	public function getEmail() {}
	//	public function getGender() {}
	//	public function getHometown() {}
	//	public function getLink() {}
	//	public function getLocale() {}
	//	public function getNickname() {}
	//	public function getPictureUrl() {}
	//	public function getUrl() {}
	//	public function isDefaultPicture() {}
	//	public function setDomain($domain) {}
}