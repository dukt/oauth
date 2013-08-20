<?php

namespace Craft;

class Oauth_ProviderModel extends BaseModel
{
    // --------------------------------------------------------------------

    /**
     * Define Attributes
     */
    public function defineAttributes()
    {
        $attributes = array(
                'id'    => AttributeType::Number,
                'providerClass' => array(AttributeType::String, 'required' => true),
                'enabled' => array(AttributeType::Bool, 'required' => true, 'default' => true),
                'clientId' => array(AttributeType::String, 'required' => true),
                'clientSecret' => array(AttributeType::String, 'required' => true),
                'token' => array(AttributeType::Mixed, 'required' => false),
            );

        return $attributes;
    }

    // --------------------------------------------------------------------
}