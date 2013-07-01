<?php

namespace Craft;

require(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');

class Oauth_SettingsController extends BaseController
{
    // --------------------------------------------------------------------

    public function actionSaveService()
    {
        Craft::log(__METHOD__, LogLevel::Info, true);
        // $class = craft()->request->getSegment(3);
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

    public function actionDeleteService()
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $id = craft()->request->getRequiredParam('id');

        craft()->oauth->deleteServiceById($id);

        craft()->userSession->setNotice(Craft::t('Service deleted.'));

        $this->redirect('oauth/settings');
    }

    // --------------------------------------------------------------------

    public function actionResetServiceToken()
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $providerClass = craft()->request->getRequiredParam('providerClass');

        craft()->oauth->resetServiceToken($providerClass);

        craft()->userSession->setNotice(Craft::t('Token Reset.'));

        $redirect = UrlHelper::getUrl('oauth/settings/'.$providerClass);

        $this->redirect($redirect);

    }

    // --------------------------------------------------------------------

    public function actionServiceCallback()
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        craft()->oauth->connectService();
    }

    // --------------------------------------------------------------------

    public function actionEnable()
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $id = craft()->request->getRequiredParam('providerId');

        if(craft()->oauth->enable($id)) {
            Craft::log(__METHOD__." : Service enabled", LogLevel::Info, true);
            craft()->userSession->setNotice(Craft::t('Service enabled.'));
        } else {
            Craft::log(__METHOD__." : Service couldn't be set as enabled.", LogLevel::Info, true);
            craft()->userSession->setError(Craft::t("Service couldn't be set as enabled."));
        }

        $this->redirect('oauth');
    }

    // --------------------------------------------------------------------

    public function actionDisable()
    {
        Craft::log(__METHOD__, LogLevel::Info, true);

        $id = craft()->request->getRequiredParam('providerId');

        if(craft()->oauth->disable($id)) {
            Craft::log(__METHOD__." : Service disabled.", LogLevel::Info, true);
            craft()->userSession->setNotice(Craft::t('Service disabled.'));
        } else {
            Craft::log(__METHOD__." : Service couldn't be set as disabled.", LogLevel::Info, true);
            craft()->userSession->setError(Craft::t("Service couldn't be set as disabled."));
        }

        $this->redirect('oauth');
    }
}