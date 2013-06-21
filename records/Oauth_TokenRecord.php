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
            'namespace' => array(AttributeType::String, 'required' => true),
            'provider' => array(AttributeType::String, 'required' => true),
            'type' => array(AttributeType::String, 'required' => true),
            'token' => array(AttributeType::String, 'column' => ColumnType::Text),
            'options' => array(AttributeType::Mixed, 'required' => false),
        );
    }


    /**
     * @return array
     */
    public function defineRelations()
    {
        return array(
            'user' => array(static::BELONGS_TO, 'UserRecord', 'onDelete' => static::CASCADE, 'required' => true),
        );
    }

    public function create()
    {
        $class = get_class($this);

        $record = new $class();

        return $record;
    }


}


// videos, Google:scope:youtube, Vimeo:scope:read/write
// videos, system, token
// videos, user, {userId}, {provider}, token