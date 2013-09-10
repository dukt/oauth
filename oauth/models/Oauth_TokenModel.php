<?php

/**
 * Craft OAuth by Dukt
 *
 * @package   Craft OAuth
 * @author    Benjamin David
 * @copyright Copyright (c) 2013, Dukt
 * @license   http://dukt.net/craft/oauth/docs#license
 * @link      http://dukt.net/craft/oauth/
 */

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

    public function getDecodedToken()
    {
        return @unserialize(base64_decode($this->token));
    }

    // --------------------------------------------------------------------

    public function getEncodedToken()
    {
        return @unserialize(base64_decode($this->token));
    }

    // --------------------------------------------------------------------

    public function getRealToken()
    {
        return $this->getDecodedToken();
    }

    // --------------------------------------------------------------------

    public function hasScope($scope)
    {
        return \Craft\craft()->oauth->scopeIsEnough($scope, $this->scope);
    }

    // --------------------------------------------------------------------
}
