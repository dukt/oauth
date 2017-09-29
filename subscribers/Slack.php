<?php

namespace Dukt\OAuth\Guzzle\Subscribers;

use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Slack implements EventSubscriberInterface
{
    // Properties
    // =========================================================================

    private $config;

    // Public Methods
    // =========================================================================

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
        $accessToken = $this->config['access_token'];
        $event['request']->getQuery()->set('token', $accessToken);
    }
}