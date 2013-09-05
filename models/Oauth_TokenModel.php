<?php

namespace Craft;

class Oauth_TokenModel extends BaseModel
{
    // --------------------------------------------------------------------

    /**
     * Define Attributes
     */
    public function defineAttributes()
    {
        $attributes = array(
                'id'    => AttributeType::Number,
	            'userMapping' => array(AttributeType::String, 'required' => false),
	            'namespace' => array(AttributeType::String, 'required' => false),
	            'provider' => array(AttributeType::String, 'required' => true),
	            'scope' => array(AttributeType::Mixed, 'required' => false),
	            'token' => array(AttributeType::String, 'column' => ColumnType::Text),
	            'userId'  => AttributeType::Number,
            );

        return $attributes;
    }

    // --------------------------------------------------------------------

    public function getRealToken()
    {
        return @unserialize(base64_decode($this->token));
    }

    // --------------------------------------------------------------------
}
