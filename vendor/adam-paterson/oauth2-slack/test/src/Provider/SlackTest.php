<?php
namespace AdamPaterson\OAuth2\Client\Test\Provider;

use AdamPaterson\OAuth2\Client\Provider\Slack;
use Mockery as m;
use ReflectionClass;

class SlackTest extends \PHPUnit_Framework_TestCase
{
    protected $provider;

    protected static function getMethod($name)
    {
        $class = new ReflectionClass('AdamPaterson\OAuth2\Client\Provider\Slack');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    protected function setUp()
    {
        $this->provider = new Slack([
            'clientId'      => 'mock_client_id',
            'clientSecret'  => 'mock_secret',
            'redirectUri'   => 'none',
        ]);
    }

    public function tearDown()
    {
        m::close();
        parent::tearDown();
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

    public function testGetResourceOwnerDetailsUrl()
    {
        $authUser = json_decode('{"ok": true,"url": "https:\/\/myteam.slack.com\/","team": "My Team","user": "cal","team_id": "T12345","user_id": "U12345"}',true);
        $token = m::mock('League\OAuth2\Client\Token\AccessToken', [['access_token' => 'mock_access_token']]);
        $token->shouldReceive('__toString')->andReturn('mock_access_token');

        $provider = m::mock('AdamPaterson\OAuth2\Client\Provider\Slack');
        $provider->shouldReceive('getAuthorizedUser')->andReturn($authUser);
        $provider->shouldReceive('getResourceOwnerDetailsUrl')->once()->andReturn('https://slack.com/api/users.info?token=mock_access_token&user=U12345');

        $url = $provider->getResourceOwnerDetailsUrl($token);
        $uri = parse_url($url);

        $this->assertEquals('/api/users.info', $uri['path']);
        $this->assertEquals('token=mock_access_token&user=U12345', $uri['query']);
    }

    public function testGetAuthorizationUrl()
    {
        $params = [];
        $url = $this->provider->getAuthorizationUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/oauth/authorize', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];
        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);
        $this->assertEquals('/api/oauth.access', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->andReturn('{"access_token": "mock_access_token", "expires_in": 3600}');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertLessThanOrEqual(time() + 3600, $token->getExpires());
        $this->assertGreaterThanOrEqual(time(), $token->getExpires());
        $this->assertNull($token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testCheckResponseThrowsIdentityProviderException()
    {
        $method = self::getMethod('checkResponse');
        $responseInterface = m::mock('Psr\Http\Message\ResponseInterface');
        $data = ['ok' => false];
        try {
            $method->invoke($this->provider, $responseInterface, $data);
        } catch (\Exception $e) {
            $this->assertEquals(400, $e->getCode());
            $this->assertEquals("Unknown error", $e->getMessage());
        }
    }

    public function testGetAuthorizedUserTestUrl()
    {
        $token = m::mock('League\OAuth2\Client\Token\AccessToken', [['access_token' => 'mock_access_token']]);
        $token->shouldReceive('__toString')->andReturn('mock_access_token');
        $url = $this->provider->getAuthorizedUserTestUrl($token);
        $uri = parse_url($url);

        $this->assertEquals('/api/auth.test', $uri['path']);
        $this->assertEquals('token=mock_access_token', $uri['query']);
    }

    public function testGetAuthorizedUserDetails()
    {
        $url = uniqid();
        $team = uniqid();
        $userName = uniqid();
        $teamId = uniqid();
        $userId = uniqid();

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('access_token=mock_access_token&expires=3600&refresh_token=mock_refresh_token&otherKey={1234}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'application/x-www-form-urlencoded']);
        $postResponse->shouldReceive('getStatusCode')->andReturn(200);

        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse->shouldReceive('getBody')->andReturn('{"ok": true,"url": "'.$url.'","user": "'.$userName.'","team": "'.$team.'","team_id": "'.$teamId.'","user_id": "'.$userId.'"}');
        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $userResponse->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(2)
            ->andReturn($postResponse, $userResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getAuthorizedUser($token);

        $this->assertEquals($userId, $user->getId());
        $this->assertEquals($url, $user->getUrl());
        $this->assertEquals($url, $user->toArray()['url']);
        $this->assertEquals($team, $user->getTeam());
        $this->assertEquals($team, $user->toArray()['team']);
        $this->assertEquals($userName, $user->getUser());
        $this->assertEquals($userName, $user->toArray()['user']);
        $this->assertEquals($teamId, $user->getTeamId());
        $this->assertEquals($teamId, $user->toArray()['team_id']);
        $this->assertEquals($userId, $user->getUserId());
        $this->assertEquals($userId, $user->toArray()['user_id']);
    }

    public function testGetResourceOwnerDetails()
    {
        $id = uniqid();
        $name = uniqid();
        $deleted = false;
        $color = uniqid();
        $profile = [
            "first_name"    =>   uniqid(),
            "last_name"     =>   uniqid(),
            "real_name"     =>   uniqid(),
            "email"         =>   uniqid(),
            "skype"         =>   uniqid(),
            "phone"         =>   uniqid(),
            "image_24"      =>   uniqid(),
            "image_32"      =>   uniqid(),
            "image_48"      =>   uniqid(),
            "image_72"      =>   uniqid(),
            "image_192"     =>   uniqid()
        ];

        $isAdmin = true;
        $isOwner = true;
        $has2FA = true;
        $hasFiles = true;

        $url = uniqid();
        $userName = uniqid();
        $team = uniqid();
        $teamId = uniqid();
        $userId = uniqid();

        $accessTokenResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $accessTokenResponse->shouldReceive('getBody')->andReturn('access_token=mock_access_token&expires=3600&refresh_token=mock_refresh_token&otherKey={1234}');
        $accessTokenResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'application/x-www-form-urlencoded']);
        $accessTokenResponse->shouldReceive('getStatusCode')->andReturn(200);

        $authUser = m::mock('Psr\Http\Message\ResponseInterface');
        $authUser->shouldReceive('getBody')->andReturn('{"ok": true,"url": "'.$url.'","user": "'.$userName.'","team": "'.$team.'","team_id": "'.$teamId.'","user_id": "'.$userId.'"}');
        $authUser->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $authUser->shouldReceive('getStatusCode')->andReturn(200);

        $authUserResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $authUserResponse->shouldReceive('getBody')->andReturn('{"ok": true,"url": "'.$url.'","team": "'.$team.'","user": "'.$userName.'","team_id": "'.$teamId.'","user_id": "'.$userId.'"}');
        $authUserResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $authUserResponse->shouldReceive('getStatusCode')->andReturn(200);

        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse->shouldReceive('getBody')->andReturn('{"ok": true,"user": {"id": "'.$id.'","name": "'.$name.'","deleted": false,"color": "'.$color.'","profile": {"first_name": "'.$profile["first_name"].'","last_name": "'.$profile["last_name"].'","real_name": "'.$profile["real_name"].'","email": "'.$profile["email"].'","skype": "'.$profile["skype"].'","phone": "'.$profile["phone"].'","image_24": "'.$profile["image_24"].'","image_32": "'.$profile["image_32"].'","image_48": "'.$profile["image_48"].'","image_72": "'.$profile["image_72"].'","image_192": "'.$profile["image_192"].'"},"is_admin": true,"is_owner": true,"has_2fa": true,"has_files": true}}');
        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $userResponse->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(3)
            ->andReturn($accessTokenResponse, $authUserResponse, $userResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getResourceOwner($token);

        $this->assertEquals($id, $user->getId());
        $this->assertEquals($id, $user->toArray()['user']['id']);
        $this->assertEquals($name, $user->getName());
        $this->assertEquals($name, $user->toArray()['user']['name']);
        $this->assertEquals($deleted, $user->isDeleted());
        $this->assertEquals($deleted, $user->toArray()['user']['deleted']);
        $this->assertEquals($color, $user->getColor());
        $this->assertEquals($color, $user->toArray()['user']['color']);
        $this->assertEquals($profile, $user->getProfile());
        $this->assertEquals($profile, $user->toArray()['user']['profile']);

        $this->assertEquals($profile['first_name'], $user->getFirstName());
        $this->assertEquals($profile['first_name'], $user->toArray()['user']['profile']['first_name']);
        $this->assertEquals($profile['last_name'], $user->getLastName());
        $this->assertEquals($profile['last_name'], $user->toArray()['user']['profile']['last_name']);
        $this->assertEquals($profile['real_name'], $user->getRealName());
        $this->assertEquals($profile['real_name'], $user->toArray()['user']['profile']['real_name']);
        $this->assertEquals($profile['email'], $user->getEmail());
        $this->assertEquals($profile['email'], $user->toArray()['user']['profile']['email']);
        $this->assertEquals($profile['skype'], $user->getSkype());
        $this->assertEquals($profile['skype'], $user->toArray()['user']['profile']['skype']);
        $this->assertEquals($profile['phone'], $user->getPhone());
        $this->assertEquals($profile['phone'], $user->toArray()['user']['profile']['phone']);
        $this->assertEquals($profile['image_24'], $user->getImage24());
        $this->assertEquals($profile['image_24'], $user->toArray()['user']['profile']['image_24']);
        $this->assertEquals($profile['image_32'], $user->getImage32());
        $this->assertEquals($profile['image_32'], $user->toArray()['user']['profile']['image_32']);
        $this->assertEquals($profile['image_48'], $user->getImage48());
        $this->assertEquals($profile['image_48'], $user->toArray()['user']['profile']['image_48']);
        $this->assertEquals($profile['image_72'], $user->getImage72());
        $this->assertEquals($profile['image_72'], $user->toArray()['user']['profile']['image_72']);
        $this->assertEquals($profile['image_192'], $user->getImage192());
        $this->assertEquals($profile['image_192'], $user->toArray()['user']['profile']['image_192']);

        $this->assertEquals($isAdmin, $user->isAdmin());
        $this->assertEquals($isAdmin, $user->toArray()['user']['is_admin']);
        $this->assertEquals($isOwner, $user->isOwner());
        $this->assertEquals($isOwner, $user->toArray()['user']['is_owner']);
        $this->assertEquals($has2FA, $user->hasTwoFactorAuthentication());
        $this->assertEquals($has2FA, $user->toArray()['user']['has_2fa']);
        $this->assertEquals($hasFiles, $user->hasFiles());
        $this->assertEquals($hasFiles, $user->toArray()['user']['has_files']);


    }
}