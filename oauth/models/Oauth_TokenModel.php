<?php

/**
 * Craft OAuth by Dukt
 *
 * @package   Craft OAuth
 * @author    Benjamin David
 * @copyright Copyright (c) 2014, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 * @link      https://dukt.net/craft/oauth/
 */

namespace Craft;

class Oauth_TokenModel extends BaseModel
{
    public function defineAttributes()
    {
        $attributes = array(
                'id'    => AttributeType::Number,
                'providerHandle'    => AttributeType::String,
                'pluginHandle'    => AttributeType::String,
                'encodedToken'    => AttributeType::String,
            );

        return $attributes;
    }

    public function getHash()
    {
        return md5($this->encodedToken);
    }

    public function getToken()
    {
        return craft()->oauth->decodeToken($this->encodedToken);
    }

    public function getPlugin()
    {
        return craft()->plugins->getPlugin($this->pluginHandle);
    }

    public function refreshToken()
    {
        try {
            craft()->oauth->refreshToken($this);
            return true;
        }
        catch(\Exception $e)
        {
            return false;
        }
    }

    public function getAccessToken()
    {
        $token = $this->getToken();

        if($token)
        {
            return $token->getAccessToken();
        }
    }

    public function getEndOfLife()
    {
        $token = $this->getToken();

        if($token)
        {
            return $token->getEndOfLife();
        }
    }
}