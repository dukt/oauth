<?php

namespace Dukt\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

class Vimeo extends AbstractProvider
{
    // Public Methods
    // =========================================================================

    public function getBaseAuthorizationUrl()
    {
        return 'https://api.vimeo.com/oauth/authorize';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return 'https://api.vimeo.com/oauth/access_token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return 'https://api.vimeo.com/me';
    }

	// Protected Methods
	// =========================================================================

    protected function getScopeSeparator()
    {
        return ' ';
    }
    
    protected function getDefaultScopes()
    {
        return ['public'];
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (!empty($data['error'])) {
            $code  = 0;
            $error = $data['error'];

            if (is_array($error)) {
                $code  = $error['code'];
                $error = $error['message'];
            }

            throw new IdentityProviderException($error, $code, $data);
        }
    }
    
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new VimeoResourceOwner($response);
    }

    /**
     * Requests resource owner details.
     *
     * @param  AccessToken $token
     * @return mixed
     */
    protected function fetchResourceOwnerDetails(AccessToken $token)
    {
        $url = $this->getResourceOwnerDetailsUrl($token);

        $request = $this->getAuthenticatedRequest(self::METHOD_GET, $url, $token, [
            'headers' => [
                'Authorization' => 'Bearer '.$token
            ]
        ]);

        return $this->getParsedResponse($request);
    }
}
