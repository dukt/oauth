<?php

namespace OAuth\Provider;

use \OAuth\OAuth2\Token\Access;

/**
 * Campaign Monitor OAuth2 Provider
 *
 * @category   Provider
 * @author     Benjamin David
 * @copyright  (c) 2013 Dukt
 * @license    http://dukt.net
 */

class CampaignMonitor extends \OAuth\OAuth2\Provider
{
    /**
     * @var  string  the method to use when requesting tokens
     */
    protected $method = 'POST';

    public function authorizeUrl()
    {
        return 'https://api.createsend.com/oauth';
    }

    public function authorize($options = array())
    {
        $state = md5(uniqid(rand(), true));

        $params = array(
            'redirect_uri'      => isset($options['redirect_uri']) ? $options['redirect_uri'] : $this->redirect_uri,
            'state'             => $state,
            'response_type'     => 'code',
            'approval_prompt'   => 'force', // - google force-recheck

            'client_id' => $this->client_id,
            'type' => 'web_server',
            'scope' => 'ViewReports,CreateCampaigns,SendCampaigns'

        );

        $params = array_merge($params, $this->params);

        return $params;
    }

    public function accessTokenUrl()
    {
        return 'https://api.createsend.com/oauth/token';
    }

    public function getUserInfo()
    {
        // Create a response from the request
        return array(
            'uid' => $this->token->access_token,
        );
    }
}
