<?php

$provider
    $clientId
    $clientSecret
    $redirectUri

$token
    $accessToken
    $expires
    $refreshToken

$providerSource
    $provider
    $token
    $service


###############

    $providerSource = craft()->oauth->getProviderSource('google');

    $provider = craft()->oauth->getProvider('google');
    $providerSource->setProvider($provider);

    $token = craft()->oauth->getToken();
    $providerSource->setToken($token);
    $providerSource->refreshAccessToken();
                            $service->refreshAccessToken($token);

# getUserDetails

    $provider =
    $token =

    $providerSource = new GoogleOAuthProviderSource;
    $providerSource->setProvider($provider);
    $providerSource->setToken($token);
    $providerSource->getUserDetails();

# refresh token

    $service->refreshAccessToken($token)

#rest

function getApi()
{
    $provider = craft()->oauth->getProvider('google');
    $token = craft()->oauth->getTokenById(123);

    $providerSource = new GoogleOAuthProviderSource;
    $providerSource->setProvider($provider);
    $providerSource->setToken($token);

    $api = new \Dukt\Rest\Api\YouTube;
    $api->setProviderSource($providerSource);
}

$api = craft()->rest->getApi('youtube');
$api->request('search');


#videos

function getGateway($gatewayHandle)
{
    $provider = craft()->oauth->getProvider('google');
    $token = craft()->oauth->getTokenById(123);

    $providerSource = new GoogleOAuthProviderSource;
    $providerSource->setProvider($provider);
    $providerSource->setToken($token);

    $gateway = new \Dukt\Videos\YouTube\Service;
    $gateway->setProviderSource($providerSource);
}


$youtube = craft()->videos->getGatway('youtube');
$youtube->search('peter doherty');











$providerSource
    $provider
    $token
    $session
    $credentials
    $service