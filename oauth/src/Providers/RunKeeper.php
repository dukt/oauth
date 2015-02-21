<?php

/**
 * Craft OAuth by Dukt
 *
 * @package   Craft OAuth
 * @author    Benjamin David
 * @copyright Copyright (c) 2015, Dukt
 * @link      https://dukt.net/craft/oauth/
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Dukt\OAuth\Providers;

class RunKeeper extends AbstractProvider {

    public $consoleUrl = 'http://runkeeper.com/partner/applications';
    public $oauthVersion = 2;

    public function getName()
    {
        return 'RunKeeper';
    }

    public function getUserDetails()
    {
        try
        {
            $response = $this->service->request('/user');
            $response = json_decode($response, true);

            $profile = $this->service->request('/profile');
            $profile = json_decode($profile, true);

            $account = array();

            $account['uid'] = $response['userID'];
            $account['name'] = $profile['name'];

            return $account;
        }
        catch(\Exception $e)
        {
            return false;
        }
    }
}