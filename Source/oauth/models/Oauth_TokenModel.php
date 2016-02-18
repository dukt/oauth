<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2016, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Craft;

class Oauth_TokenModel extends BaseModel
{
    // Public Methods
    // =========================================================================

    /**
     * Get Plugin
     */
    public function getPlugin()
    {
        return craft()->plugins->getPlugin($this->pluginHandle);
    }

    // Protected Methods
    // =========================================================================

    /**
     * Define Attributes
     */
    protected function defineAttributes()
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

    public function getProvider()
    {
        return craft()->oauth->getProvider($this->providerHandle);
    }

    public function getToken()
    {
        return OauthHelper::getRealToken($this);
    }
}