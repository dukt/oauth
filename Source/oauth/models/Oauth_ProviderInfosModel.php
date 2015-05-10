<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2015, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Craft;

class Oauth_ProviderInfosModel extends BaseModel
{
    // Properties
    // =========================================================================

    public $source;

    // Public Methods
    // =========================================================================

    public function defineAttributes()
    {
        $attributes = array(
                'id'    => AttributeType::Number,
                'class' => array(AttributeType::String, 'required' => true),
                'clientId' => array(AttributeType::String, 'required' => false),
                'clientSecret' => array(AttributeType::String, 'required' => false),
            );

        return $attributes;
    }

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getHandle()
    {
        return strtolower($this->class);
    }

    public function getName()
    {
        return $this->source->getName();
    }
}