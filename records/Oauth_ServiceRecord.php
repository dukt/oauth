<?php

namespace Craft;

class Oauth_ServiceRecord extends BaseRecord
{
    /**
     * Get Table Name
     */
    public function getTableName()
    {
        return 'oauth_services';
    }

    // --------------------------------------------------------------------

    /**
     * Define Attributes
     */
    public function defineAttributes()
    {
        return array(
            'providerClass' => array(AttributeType::String, 'required' => true, 'unique' => true),
            'enabled' => array(AttributeType::Bool, 'required' => true, 'default' => true),
            'clientId' => array(AttributeType::String, 'required' => true),
            'clientSecret' => array(AttributeType::String, 'required' => true)
        );
    }

    // --------------------------------------------------------------------

    public function create()
    {
        $class = get_class($this);

        $record = new $class();

        return $record;
    }

    // --------------------------------------------------------------------
}