<?php

namespace Saf33r\OAuth2\Client\Test\Provider;

use Mockery as m;
use Saf33r\OAuth2\Client\Provider\Vimeo;

class VimeoTest extends \PHPUnit_Framework_TestCase
{
    protected $provider;

    protected function setUp()
    {
        $this->provider = new Vimeo([
            'clientId'     => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri'  => 'none',
        ]);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testScopes()
    {
        $options = ['scope' => [uniqid(), uniqid()]];

        $url = $this->provider->getAuthorizationUrl($options);

        $this->assertContains(urlencode(implode(' ', $options['scope'])), $url);
    }

    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/oauth/authorize', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/oauth/access_token', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->andReturn('{"access_token":"mock_access_token", "scope":"repo,gist", "token_type":"bearer"}');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertNull($token->getExpires());
        $this->assertNull($token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testUserData()
    {
        $id = rand(1000, 9999);
        $name = uniqid();
        $username = 'user' . $id;
        $image = uniqid();
        $link = 'https://vimeo.com/' . $username;
        $resourceKey = uniqid();
        $tokenScope = 'public';

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('access_token=mock_access_token&expires=3600&refresh_token=mock_refresh_token&otherKey=1234&scope=' . $tokenScope);
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'application/x-www-form-urlencoded']);
        $postResponse->shouldReceive('getStatusCode')->andReturn(200);

        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse->shouldReceive('getBody')->andReturn($this->mockGetMe($id, $name, $link, $image, $resourceKey));
        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $userResponse->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(2)->andReturn($postResponse, $userResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getResourceOwner($token);

        $this->assertEquals($id, $user->getId());
        $this->assertEquals($name, $user->getName());
        $this->assertEquals($username, $user->getUsername());
        $this->assertEquals($link, $user->getLink());
        $this->assertEquals($image, $user->getAvatar());

        // test getTokenScope
        $this->assertEquals($tokenScope, $user->getTokenScope());

        // test toArray
        $toArray = $user->toArray();
        $this->assertFalse(empty($toArray));
        $this->assertTrue(is_array($toArray));
        $this->assertEquals($name, $toArray['name']);
    }

    /**
     * @expectedException \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     **/
    public function testExceptionThrownWhenErrorObjectReceived()
    {
        $message = uniqid();
        $status = rand(400, 600);
        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn(' {"error":"' . $message . '", "code": "' . $status . '"}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $postResponse->shouldReceive('getStatusCode')->andReturn($status);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($postResponse);
        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    protected function mockGetMe($id, $name, $link, $image, $resourceKey)
    {
        return '{
            "uri": "/users/' . $id . '",
            "name": "' . $name . '",
            "link": "' . $link . '",
            "location": null,
            "bio": null,
            "created_time": "2016-01-01T00:00:21+00:00",
            "account": "basic",
            "pictures": {
                "uri": "/users/' . $id . '/pictures/' . $id . '",
                "active": true,
                "type": "custom",
                "sizes": [
                    {
                        "width": 30,
                        "height": 30,
                        "link": "' . $image . '"
                    },
                    {
                        "width": 75,
                        "height": 75,
                        "link": "' . $image . '"
                    },
                    {
                        "width": 100,
                        "height": 100,
                        "link": "' . $image . '"
                    },
                    {
                        "width": 300,
                        "height": 300,
                        "link": "' . $image . '"
                    }
                ],
                "resource_key": "' . $resourceKey . '"
            },
            "websites": [],
            "metadata": {
                "connections": {
                    "activities": {
                        "uri": "/users/' . $id . '/activities",
                        "options": [
                            "GET"
                        ]
                    },
                    "albums": {
                        "uri": "/users/' . $id . '/albums",
                        "options": [
                            "GET"
                        ],
                        "total": 0
                    },
                    "channels": {
                        "uri": "/users/' . $id . '/channels",
                        "options": [
                            "GET"
                        ],
                        "total": 0
                    },
                    "feed": {
                        "uri": "/users/' . $id . '/feed",
                        "options": [
                            "GET"
                        ]
                    },
                    "followers": {
                        "uri": "/users/' . $id . '/followers",
                        "options": [
                            "GET"
                        ],
                        "total": 0
                    },
                    "following": {
                        "uri": "/users/' . $id . '/following",
                        "options": [
                            "GET"
                        ],
                        "total": 0
                    },
                    "groups": {
                        "uri": "/users/' . $id . '/groups",
                        "options": [
                            "GET"
                        ],
                        "total": 0
                    },
                    "likes": {
                        "uri": "/users/' . $id . '/likes",
                        "options": [
                            "GET"
                        ],
                        "total": 0
                    },
                    "portfolios": {
                        "uri": "/users/' . $id . '/portfolios",
                        "options": [
                            "GET"
                        ],
                        "total": 0
                    },
                    "videos": {
                        "uri": "/users/' . $id . '/videos",
                        "options": [
                            "GET"
                        ],
                        "total": 0
                    },
                    "watchlater": {
                        "uri": "/users/' . $id . '/watchlater",
                        "options": [
                            "GET"
                        ],
                        "total": 0
                    },
                    "shared": {
                        "uri": "/users/' . $id . '/shared/videos",
                        "options": [
                            "GET"
                        ],
                        "total": 0
                    },
                    "pictures": {
                        "uri": "/users/' . $id . '/pictures",
                        "options": [
                            "GET",
                            "POST"
                        ],
                        "total": 1
                    }
                }
            },
            "preferences": {
                "videos": {
                    "privacy": "anybody"
                }
            },
            "content_filter": [
                "language",
                "drugs",
                "violence",
                "nudity",
                "safe",
                "unrated"
            ],
            "resource_key": "' . $resourceKey . '"
        }';
    }
}
