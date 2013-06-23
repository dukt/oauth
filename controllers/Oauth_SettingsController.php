<?php

namespace Craft;

require(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');

class Oauth_SettingsController extends BaseController
{
    // --------------------------------------------------------------------

    public function actionSaveService()
    {

        // $class = craft()->request->getSegment(3);
        $class = craft()->request->getParam('serviceProviderClass');

        $model = new Oauth_ServiceModel();



        $attributes = craft()->request->getPost('service');

        $attributes['providerClass'] = $class;

        $model->setAttributes($attributes);


        if (craft()->oauth->saveService($model)) {
            craft()->userSession->setNotice(Craft::t('Service saved.'));

            $redirect = craft()->request->getPost('redirect');

            $this->redirect($redirect);
        } else {
            craft()->userSession->setError(Craft::t("Couldn't save service."));

            craft()->urlManager->setRouteVariables(array('service' => $model));
        }
    }

    // --------------------------------------------------------------------

    public function actionDeleteService()
    {
        $id = craft()->request->getRequiredParam('id');

        craft()->oauth->deleteServiceById($id);

        craft()->userSession->setNotice(Craft::t('Service deleted.'));

        $this->redirect('oauth/settings');
    }

    // --------------------------------------------------------------------

    public function actionResetServiceToken()
    {
        $providerClass = craft()->request->getRequiredParam('providerClass');

        craft()->oauth->resetServiceToken($providerClass);

        craft()->userSession->setNotice(Craft::t('Token Reset.'));

        $redirect = UrlHelper::getUrl('oauth/settings/'.$providerClass);

        $this->redirect($redirect);

    }

    // --------------------------------------------------------------------

    public function actionServiceCallback()
    {
        craft()->oauth->connectService();
    }

    // --------------------------------------------------------------------

    public function actionEnable()
    {
        $id = craft()->request->getRequiredParam('providerId');

        if(craft()->oauth->enable($id)) {
            craft()->userSession->setNotice(Craft::t('Service enabled.'));
        } else {
            craft()->userSession->setError(Craft::t("Service couldn't be set as enabled."));
        }

        $this->redirect('oauth');
    }

    // --------------------------------------------------------------------

    public function actionDisable()
    {
        $id = craft()->request->getRequiredParam('providerId');

        if(craft()->oauth->disable($id)) {
            craft()->userSession->setNotice(Craft::t('Service disabled.'));
        } else {
            craft()->userSession->setError(Craft::t("Service couldn't be set as disabled."));
        }

        $this->redirect('oauth');
    }
}