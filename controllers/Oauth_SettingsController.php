<?php

namespace Craft;

require(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');

class Oauth_SettingsController extends BaseController
{
    // --------------------------------------------------------------------

    public function actionSaveService()
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $class = craft()->request->getParam('serviceProviderClass');

        $model = new Oauth_ServiceModel();



        $attributes = craft()->request->getPost('service');

        $attributes['providerClass'] = $class;

        $model->setAttributes($attributes);


        if (craft()->oauth->saveService($model)) {
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

        craft()->oauth->deleteTokenById($id);

        craft()->userSession->setNotice(Craft::t('Token deleted.'));

        $this->redirect($_SERVER['HTTP_REFERER']);
    }

    // --------------------------------------------------------------------

}