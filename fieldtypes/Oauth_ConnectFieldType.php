<?php


namespace Craft;

class Oauth_ConnectFieldType extends BaseFieldType
{

	/**
	 * Block type name
	 */
	public function getName()
	{
		return Craft::t('OAuth Connect');
	}

	// --------------------------------------------------------------------

	/**
	 * Save it
	 */
	// public function defineContentAttribute()
	// {
	// 	return AttributeType::Bool;
	// }

	// --------------------------------------------------------------------

	/**
	 * Show field
	 */
	public function getInputHtml($name, $value)
	{
		return craft()->templates->render('oauth/fields/connect', array(
			'element' => $this->element,
			'elementType' => $this->element->getElementType()
		));
	}

	// --------------------------------------------------------------------

	// /**
	//  * Prep value
	//  */
	// public function prepValue($isShared)
	// {
	// 	return $isShared;
	// }

	// // --------------------------------------------------------------------

	// public function onAfterElementSave()
	// {
 //        $element = $this->element;
 //        $fields = $element->section->getFieldLayout()->getFields();

 //        foreach($fields as $field) {
 //            if($field->getField()->type == 'Facebook_Publish') {
 //                $handle = $field->getField()->handle;
 //                $settings = $field->getField()->settings;

 //                if($element->{$handle} == 1) {
 //                    craft()->facebook->publish($element, $settings);
 //                }
 //            }
 //        }
	// }
	
	// // --------------------------------------------------------------------

 //    protected function defineSettings()
 //    {
 //        return array(
 //            'templatePath' => array(AttributeType::String)
 //        );
 //    }

	// // --------------------------------------------------------------------

 //    public function getSettingsHtml()
 //    {
 //        return craft()->templates->render('facebook/settings', array(
 //            'settings' => $this->getSettings()
 //        ));
 //    }

	// --------------------------------------------------------------------
}
