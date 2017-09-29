<?php

namespace Craft;

class Oauth_ResourceOwnerModel extends BaseModel
{
    // Public Methods
    // =========================================================================

    public function getId()
    {
        return $this->remoteId;
    }
    
    public function getUid()
    {
        return $this->remoteId;
    }
    
    // Protected Methods
    // =========================================================================

    /**
     * Define Attributes
     */
    protected function defineAttributes()
    {
        $attributes = array(
            'remoteId' => AttributeType::Number,
            'email' => AttributeType::String,
            'name' => AttributeType::String,
        );

        return $attributes;
    }

}
