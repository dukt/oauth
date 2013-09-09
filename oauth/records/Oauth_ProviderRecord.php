<?php

namespace Craft;

class Oauth_ProviderRecord extends BaseRecord
{
    /**
     * Get Table Name
     */
    public function getTableName()
    {
        return 'oauth_providers';
    }

    // --------------------------------------------------------------------

    /**
     * Define Attributes
     */
    public function defineAttributes()
    {
        return array(
            'class' => array(AttributeType::String, 'required' => true, 'unique' => true),
            'clientId' => array(AttributeType::String, 'required' => false),
            'clientSecret' => array(AttributeType::String, 'required' => false)
        );
    }

    // --------------------------------------------------------------------
}