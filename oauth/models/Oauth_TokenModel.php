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

                'id' => AttributeType::Number,
                'providerHandle' => AttributeType::String,
                'pluginHandle' => AttributeType::String,

                'accessToken' => AttributeType::String,
                'secret' => AttributeType::String,
                'endOfLife' => AttributeType::String,
                'refreshToken' => AttributeType::String,
            );

        return $attributes;
    }

    public function getHash()
    {
        return md5(array(
                $this->accessToken,
                $this->secret,
                $this->endOfLife,
                $this->refreshToken
            ));
    }

    public function getDecodedToken()
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
}