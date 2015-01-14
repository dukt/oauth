<?php

/**
 * Craft OAuth by Dukt
 *
 * @package   Craft OAuth
 * @author    Benjamin David
 * @copyright Copyright (c) 2015, Dukt
 * @link      https://dukt.net/craft/oauth/
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Craft;

class Oauth_ProviderInfosModel extends BaseModel
{
    public $source;

    public function defineAttributes()
    {
        $attributes = array(
                'id'    => AttributeType::Number,
                'class' => array(AttributeType::String, 'required' => true),
                'clientId' => array(AttributeType::String, 'required' => false),
                'clientSecret' => array(AttributeType::String, 'required' => false),
            );

        return $attributes;
    }

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getHandle()
    {
        return strtolower($this->class);
    }

    public function getName()
    {
        return $this->source->getName();
    }

    public function getAccount()
    {
        try {
            return $this->source->getAccount();
        }
        catch(\Exception $e)
        {
            // todo: log
            return false;
        }
    }

    public function isConfigured()
    {
        if(!empty($this->clientId))
        {
            return true;
        }

        return false;
    }

    public function getTokens()
    {
        return craft()->oauth->getTokensByProvider($this->getHandle());
    }
}