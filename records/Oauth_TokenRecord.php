<?php

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

    // --------------------------------------------------------------------

    /**
     * Define Attributes
     */
    public function defineAttributes()
    {
        return array(
            'userMapping' => array(AttributeType::String, 'required' => false),
            'namespace' => array(AttributeType::String, 'required' => false),
            'provider' => array(AttributeType::String, 'required' => true),
            'scope' => array(AttributeType::Mixed, 'required' => false),
            'token' => array(AttributeType::String, 'column' => ColumnType::Text),
        );
    }

    // --------------------------------------------------------------------

    public function defineRelations()
    {
        return array(
            'user' => array(static::BELONGS_TO, 'UserRecord', 'onDelete' => static::CASCADE, 'required' => false),
        );
    }

    // --------------------------------------------------------------------
}