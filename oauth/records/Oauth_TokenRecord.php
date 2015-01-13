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

class Oauth_TokenRecord extends BaseRecord
{
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
            'encodedToken' => array(AttributeType::String, 'column' => ColumnType::Text),

            'accessToken' => AttributeType::String,
            'secret' => AttributeType::String,
            'expires' => AttributeType::String,
            'refreshToken' => AttributeType::String,
        );
    }
}
