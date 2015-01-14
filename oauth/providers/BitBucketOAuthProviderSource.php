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

namespace OAuthProviderSources;

class BitBucketOAuthProviderSource extends BaseOAuthProviderSource {

    public $consoleUrl = 'https://bitbucket.org/account/';
    public $oauthVersion = 2;

    public function getName()
    {
        return 'BitBucket';
    }

    public function getUserDetails()
    {
        try {

            $userResponse = $this->service->request('user');
            $userResponse = json_decode($userResponse, true);

            $user = $userResponse['user'];

            $emailResponse = $this->service->request('/users/'.$user['username'].'/emails');
            $emailResponse = json_decode($emailResponse, true);
            $emails = $emailResponse;
            $email = $emails[0]['email'];

            $account = array();

            //$account['uid'] = $user['username'];
            $account['name'] = $user['display_name'];
            $account['username'] = $user['username'];
            $account['email'] = $email;

            return $account;
        }
        catch(\Exception $e)
        {
            // todo
        }
    }
}