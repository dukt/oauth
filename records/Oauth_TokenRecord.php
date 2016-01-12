<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2016, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Craft;

class Oauth_TokenRecord extends BaseRecord
{
    // Public Methods
    // =========================================================================

    /**
     * Get Table Name
     */
    public function getTableName()
    {
        return 'oauth_tokens';
    }

    /**
     * Define Attributes
     */
    public function defineAttributes()
    {
        return array(
            'providerHandle' => array(AttributeType::String, 'required' => true),
            'pluginHandle' => array(AttributeType::String, 'required' => true),

            'accessToken' => AttributeType::String,
            'secret' => AttributeType::String,
            'endOfLife' => AttributeType::String,
            'refreshToken' => AttributeType::String,
        );
    }
}
