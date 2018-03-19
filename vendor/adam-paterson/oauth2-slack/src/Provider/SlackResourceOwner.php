<?php

namespace AdamPaterson\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

/**
 * Class SlackResourceOwner
 *
 * @author Adam Paterson <hello@adampaterson.co.uk>
 *
 * @package AdamPaterson\OAuth2\Client\Provider
 */
class SlackResourceOwner implements ResourceOwnerInterface
{

    protected $response;

    public function __construct(array $response)
    {
        $this->response = $response;
    }

    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }

    public function getId()
    {
        return $this->response['user']['id'] ?: null;
    }


    public function getName()
    {
        return $this->response['user']['name'] ?: null;
    }

    public function isDeleted()
    {
        return $this->response['user']['deleted'] ?: null;
    }

    public function getColor()
    {
        return $this->response['user']['color'] ?: null;
    }

    public function getProfile()
    {
        return $this->response['user']['profile'] ?: null;
    }

    public function getFirstName()
    {
        return $this->response['user']['profile']['first_name'] ?: null;
    }

    public function getLastName()
    {
        return $this->response['user']['profile']['last_name'] ?: null;
    }

    public function getRealName()
    {
        return $this->response['user']['profile']['real_name'] ?: null;
    }

    public function getEmail()
    {
        return $this->response['user']['profile']['email'] ?: null;
    }

    public function getSkype()
    {
        return $this->response['user']['profile']['skype'] ?: null;
    }

    public function getPhone()
    {
        return $this->response['user']['profile']['phone'] ?: null;
    }

    public function getImage24()
    {
        return $this->response['user']['profile']['image_24'] ?: null;
    }

    public function getImage32()
    {
        return $this->response['user']['profile']['image_32'] ?: null;
    }

    public function getImage48()
    {
        return $this->response['user']['profile']['image_48'] ?: null;
    }

    public function getImage72()
    {
        return $this->response['user']['profile']['image_72'] ?: null;
    }

    public function getImage192()
    {
        return $this->response['user']['profile']['image_192'] ?: null;
    }

    public function isAdmin()
    {
        return $this->response['user']['is_admin'] ?: null;
    }

    public function isOwner()
    {
        return $this->response['user']['is_owner'] ?: null;
    }

    public function hasTwoFactorAuthentication()
    {
        return $this->response['user']['has_2fa'] ?: null;
    }

    public function hasFiles()
    {
        return $this->response['user']['has_files'] ?: null;
    }
}
