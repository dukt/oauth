<?php

namespace League\OAuth2\Client\Provider;

class GoogleUser implements ResourceOwnerInterface
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
        return $this->response['id'];
    }

    /**
     * Get perferred display name.
     *
     * @return string
     */
    public function getName()
    {
        if (!empty($this->response['name'])) {
            return $this->response['name'];
        }
    }

    /**
     * Get perferred first name.
     *
     * @return string
     */
    public function getFirstName()
    {
        if (!empty($this->response['given_name'])) {
            return $this->response['given_name'];
        }
    }

    /**
     * Get perferred last name.
     *
     * @return string
     */
    public function getLastName()
    {
        if (!empty($this->response['family_name'])) {
            return $this->response['family_name'];
        }
    }

    /**
     * Get email address.
     *
     * @return string|null
     */
    public function getEmail()
    {
        if (!empty($this->response['email'])) {
            return $this->response['email'];
        }
    }

    /**
     * Get avatar image URL.
     *
     * @return string|null
     */
    public function getAvatar()
    {
        if (!empty($this->response['picture'])) {
            return $this->response['picture'];
        }
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
