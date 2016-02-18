<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2016, Dukt
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

    /**
     * Set Source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * Get Handle
     */
    public function getHandle()
    {
        return strtolower($this->class);
    }

    /**
     * Get Name
     */
    public function getName()
    {
        return $this->source->getName();
    }

    // Protected Methods
    // =========================================================================

    /**
     * Define Attributes
     */
    protected function defineAttributes()
    {
        $attributes = array(
                'id'    => AttributeType::Number,
                'class' => array(AttributeType::String, 'required' => true),
                'clientId' => array(AttributeType::String, 'required' => false),
                'clientSecret' => array(AttributeType::String, 'required' => false),
            );

        return $attributes;
    }
}