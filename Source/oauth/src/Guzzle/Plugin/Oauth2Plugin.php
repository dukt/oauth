<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2016, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Dukt\OAuth\Guzzle\Plugin;

use Guzzle\Common\Event;
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
        $request->setHeader('Authorization', 'Bearer ' . $accessToken);
    }
}