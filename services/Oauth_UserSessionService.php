<?php
namespace Craft;

class Oauth_UserSessionService extends UserSessionService {

    private $_identity;
    public $allowAutoLogin = true;

    // --------------------------------------------------------------------

    public function login($token)
    {
        $this->_identity = new TokenIdentity($token);
        $this->_identity->authenticate();

        // Was the login successful?
        if ($this->_identity->errorCode == UserIdentity::ERROR_NONE)
        {
            // Get how long this session is supposed to last.
            $seconds = 1000;
            $this->authTimeout = $seconds;

            $id = $this->_identity->getId();

            $states = $this->_identity->getPersistentStates();

            // Run any before login logic.
            if ($this->beforeLogin($id, $states, false))
            {
                $this->changeIdentity($id, $this->_identity->getName(), $states);

                if ($seconds > 0)
                {
                    if ($this->allowAutoLogin)
                    {
                        $user = craft()->users->getUserById($id);

                        if ($user)
                        {
                            // Save the necessary info to the identity cookie.
                            $sessionToken = StringHelper::UUID();
                            $hashedToken = craft()->security->hashString($sessionToken);
                            $uid = craft()->users->handleSuccessfulLogin($user, $hashedToken['hash']);
                            $userAgent = craft()->request->userAgent;

                            $data = array(
                                $this->getName(),
                                $sessionToken,
                                $uid,
                                $seconds,
                                $userAgent,
                                $this->saveIdentityStates(),
                            );

                            $this->saveCookie('', $data, $seconds);
                        }
                        else
                        {
                            throw new Exception(Craft::t('Could not find a user with Id of {userId}.', array('{userId}' => $this->getId())));
                        }
                    }
                    else
                    {
                        throw new Exception(Craft::t('{class}.allowAutoLogin must be set true in order to use cookie-based authentication.', array('{class}' => get_class($this))));
                    }
                }

                // $this->_sessionRestoredFromCookie = false;
                // $this->_userRow = null;

                // Run any after login logic.
                $this->afterLogin(false);
            }

            return !$this->getIsGuest();
        }
    }
}