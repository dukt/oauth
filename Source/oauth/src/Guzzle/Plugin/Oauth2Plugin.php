<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2015, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Dukt\OAuth\Guzzle\Plugin;

use Guzzle\Common\Event;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Message\RequestInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * OAuth2 plugin
 * @link http://tools.ietf.org/html/rfc6749
 */
class Oauth2Plugin implements EventSubscriberInterface
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public static function getSubscribedEvents()
    {
        return array(
            'request.before_send' => 'onRequestBeforeSend'
        );
    }

    public function onRequestBeforeSend(Event $event)
    {
        $request = $event['request'];
        $accessToken = $this->config['access_token'];

        switch($this->config['authorization_method'])
        {
            case 'oauth2_access_token':
            $request->getQuery()->set('oauth2_access_token', $accessToken);
            break;

            default:
            $request->getQuery()->set('access_token', $accessToken);
        }
    }


}