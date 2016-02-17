<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2016, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Craft;

class Oauth_SettingsController extends BaseController
{
    /**
     * Settings
     *
     * @return null
     */
    public function actionIndex()
    {
        $plugin = craft()->plugins->getPlugin('oauth');

        $variables['settings'] = $plugin->getSettings();


        $this->renderTemplate('oauth/settings/_index', $variables);
    }
}