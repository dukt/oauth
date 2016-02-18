<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2016, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Craft;

class Oauth_ProviderInfosRecord extends BaseRecord
{
    // Public Methods
    // =========================================================================

    /**
     * Get Table Name
     */
    public function getTableName()
    {
        return 'oauth_providers';
    }

    /**
     * Define Attributes
     */
    public function defineAttributes()
    {
        return array(
            'class' => array(AttributeType::String, 'required' => true),
            'clientId' => array(AttributeType::String, 'required' => false),
            'clientSecret' => array(AttributeType::String, 'required' => false)
        );
    }

    /**
     * @return array
     */
    public function defineIndexes()
    {
        return array(
            array('columns' => array('class'), 'unique' => true),
        );
    }
}