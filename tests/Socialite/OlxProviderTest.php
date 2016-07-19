<?php

namespace Tests\Socialite;

use Tests\TestCase;
use Mockery as m;
use Laravel\Socialite\Two\User as TwoUser;

class OlxProviderTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->request = m::mock('Illuminate\Http\Request');
        $this->clientId = 'abcdef';
        $this->redirectUrl = 'http://localhost/redirect';
        $this->clientSecret = '123456';
        $this->olx = new OlxStub($this->request, $this->clientId, $this->clientSecret, $this->redirectUrl);
    }

    public function testImplements()
    {
        $this->assertInstanceOf('Laravel\Socialite\Two\AbstractProvider', $this->olx);
        $this->assertInstanceOf('Laravel\Socialite\Two\ProviderInterface', $this->olx);
        $this->assertAttributeEquals(['basic_user_info'], 'scopes', $this->olx);
    }

    public function testGetTokenUrlImplementRedirect()
    {
        $state = '';
        $session = m::mock('session');
        $session->shouldreceive('set')->once();
        
        $this->request->shouldreceive('session')->once()->andReturn($session);

        $response = $this->olx->redirect();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        
        $url = 'https://auth.olx.com.br/oauth?';
        $url .= 'client_id=%s&redirect_uri=%s';
        $url .= '&scope=basic_user_info';
        $url .= '&response_type=code';
        $url .= '&state=';
        $pattern = preg_quote($url, '/');
        $pattern = sprintf("/^{$pattern}[a-zA-Z0-9]{40}$/", $this->clientId, urlencode($this->redirectUrl));
        $this->assertRegExp($pattern, $response->getTargetUrl());
    }

    public function testImplementAccessTokenResponse()
    {
        $tokeUrl = 'https://auth.olx.com.br/oauth/token';
        $postKey = (version_compare(\GuzzleHttp\ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';
        $data = [
            'headers' => ['Accept' => 'application/json'],
            $postKey => [
                'code' => 'code123',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUrl,
                'grant_type' => 'authorization_code',
            ],
        ];

        $response = m::mock('StdClass');
        $response->shouldreceive('getBody')->once()->andReturn('{"foo":"bar"}');

        $this->olx->getHttpClient()
            ->shouldreceive('post')
            ->once()
            ->with($tokeUrl, $data)
            ->andReturn($response);

        $this->assertEquals(['foo' => 'bar'], $this->olx->getAccessTokenResponse('code123'));
    }

    public function testUserFromToken()
    {
        $data = ['user_email' => 'user@email.com', 'user_name' => 'UserOlx'];
        $response = m::mock('StdClass');
        $response->shouldreceive('getBody')->once()->andReturn(json_encode($data));

        $token = 'asdfghjkl';
        $userUrl = 'https://apps.olx.com.br/oauth_api/basic_user_info​?access_token='.$token;

        $this->olx->getHttpClient()
            ->shouldreceive('get')
            ->once()
            ->with($userUrl)
            ->andReturn($response);

        $userExpected = new TwoUser;
        $userExpected->setRaw($data);
        $userExpected->id = $data['user_email'];
        $userExpected->name = $data['user_name'];
        $userExpected->email = $data['user_email'];
        $userExpected->token = $token;

        $this->assertEquals($userExpected, $this->olx->userFromToken($token));
    }
}

class OlxStub extends \NwLaravel\Socialite\OlxProvider
{
    public $http;

    /**
     * Get a fresh instance of the Guzzle HTTP client.
     *
     * @return \GuzzleHttp\Client
     */
    public function getHttpClient()
    {
        if ($this->http) {
            return $this->http;
        }

        return $this->http = m::mock('StdClass');
    }
}
