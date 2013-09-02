<?php

namespace Craft;

require(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');

class Oauth_SettingsController extends BaseController
{
    // --------------------------------------------------------------------

    public function actionProviderSave()
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $class = craft()->request->getParam('providerClass');

        $model = new Oauth_ProviderModel();



        $attributes = craft()->request->getPost('provider');

        $attributes['providerClass'] = $class;

        $model->setAttributes($attributes);


        if (craft()->oauth_providers->providerSave($model)) {
            Craft::log(__METHOD__." : Service Saved", LogLevel::Info, true);
            craft()->userSession->setNotice(Craft::t('Service saved.'));

            $redirect = craft()->request->getPost('redirect');

            $this->redirect($redirect);
        } else {
            Craft::log(__METHOD__." : Could not save service", LogLevel::Info, true);

            craft()->userSession->setError(Craft::t("Couldn't save service."));

            craft()->urlManager->setRouteVariables(array('service' => $model));
        }
    }

    // --------------------------------------------------------------------

    public function actionDeleteToken()
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $id = craft()->request->getRequiredParam('id');

        craft()->oauth_tokens->tokenDeleteById($id);

        craft()->userSession->setNotice(Craft::t('Token deleted.'));

        $this->redirect($_SERVER['HTTP_REFERER']);
    }

    // --------------------------------------------------------------------

}