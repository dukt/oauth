<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2015, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Craft;

class Oauth_TokenModel extends BaseModel
{
    // Public Methods
    // =========================================================================

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