<?php


namespace AdamPaterson\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class SlackAuthorizedUser implements ResourceOwnerInterface
{
    protected $response;

    /**
     * SlackAuthorizedUser constructor.
     *
     * @param $response
     */
    public function __construct(array $response)
    {
        $this->response = $response;
    }

    /**
     * Returns the identifier of the authorized resource owner.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->response['user_id'];
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

    public function getUrl()
    {
        return $this->response['url'] ?: null;
    }

    public function getTeam()
    {
        return $this->response['team'] ?: null;
    }

    public function getUser()
    {
        return $this->response['user'] ?: null;
    }

    public function getTeamId()
    {
        return $this->response['team_id'] ?: null;
    }

    public function getUserId()
    {
        return $this->response['user_id'] ?: null;
    }
}
