<?php

namespace Dukt\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class VimeoResourceOwner implements ResourceOwnerInterface
{
    /**
     * @var array
     */
    protected $response;

    /**
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->response = $response;
    }

	public function getId()
	{
		$uri = $this->response['uri'];

		$id = substr($uri, strrpos($uri, "/") + 1);

		return $id;
	}

	/**
	 * Get perferred display name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->response['name'] ?: null;
	}

	/**
	 * Returns the Vimeo URL for the user as a string if available.
	 *
	 * @return string|null
	 */
	public function getLink()
	{
		return $this->response['link'] ?: null;
	}

	/**
	 * Returns the user's location
	 *
	 * @return string|null
	 */
	public function getLocation()
	{
		return $this->response['location'] ?: null;
	}

	/**
	 * Returns the bio for the user as a string if present.
	 *
	 * @return string|null
	 */
	public function getBio()
	{
		return $this->response['bio'] ?: null;
	}

	/**
	 * Get avatar image URL.
	 *
	 * @return string|null
	 */
	public function getAvatar()
	{
		$imageUrl = null;
		$maxSize = 0;

		if(isset($this->response['pictures']) && is_array($this->response['pictures']))
		{
			foreach($this->response['pictures'] as $picture)
			{
				if($picture['width'] > $maxSize)
				{
					$maxSize = $picture['width'];
					$imageUrl = $picture['link'];
				}
			}
		}

		return $imageUrl;
	}

    /**
     * Get user data as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
