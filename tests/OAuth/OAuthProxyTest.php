<?php

namespace Tests\OAuth;

use Tests\TestCase;
use Mockery as m;
use NwLaravel\OAuth\OAuthProxy;
use NwLaravel\OAuth\OAuthClientEntity;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Psr\Http\Message\ResponseInterface;

class OAuthProxyTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->config = m::mock('config');
        $this->config
            ->shouldReceive('get')
            ->with('app.url', null)
            ->andReturn('http://localhost');

        $this->config
            ->shouldReceive('get')
            ->with('nwlaravel.oauth.urlToken', '/oauth/access-token')
            ->andReturn('/api/oauth/access-token');
            
        $this->app->instance('config', $this->config);
    }

    public function testConstructSetters()
    {
        $oauthClient = m::mock(OAuthClientEntity::class);
        $clientHttp = m::mock(Client::class);
        $proxy = new OAuthProxy($oauthClient, $clientHttp);

        $proxyUrl = 'http://localhost/api/oauth/access-token';

        $this->assertAttributeEquals($oauthClient, 'oauthClient', $proxy);
        $this->assertAttributeEquals($clientHttp, 'clientHttp', $proxy);
        $this->assertAttributeEquals($proxyUrl, 'proxyUrl', $proxy);
    }

    public function testAttemptLoginThrowRequestException()
    {
        $oauthClient = m::mock(OAuthClientEntity::class);
        $clientHttp = m::mock(Client::class);
        $proxy = new OAuthProxy($oauthClient, $clientHttp);

        $client_id = 3;
        $credentials = ['email' => 'test@test.com', 'password' => '12345'];

        $client = new \StdClass;
        $client->secret = 'IRNSHUEC4D3';
        $collection = new Collection([$client]);
        $oauthClient->shouldReceive('where')->once()->with('id', $client_id)->andReturn($collection);

        $data = array_merge([
            'client_id'     => $client_id,
            'client_secret' => 'IRNSHUEC4D3',
            'grant_type'    => 'password',
        ], $credentials);

        $expected = m::mock('Illuminate\Http\JsonResponse[]');
        $response = m::mock('Illuminate\Contracts\Routing\ResponseFactory');
        $response->shouldReceive('json')->once()->andReturn($expected);
        $this->app->instance('Illuminate\Contracts\Routing\ResponseFactory', $response);

        $request = m::mock(RequestInterface::class);

        $clientHttp->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('Erro ao buscar', $request));

        $return = $proxy->attemptLogin($client_id, $credentials);

        $this->assertInstanceOf('Illuminate\Http\JsonResponse', $return);
        $this->assertEquals($expected, $return);
    }

    public function testAttemptLoginThrowException()
    {
        $oauthClient = m::mock(OAuthClientEntity::class);
        $clientHttp = m::mock(Client::class);
        $proxy = new OAuthProxy($oauthClient, $clientHttp);

        $client_id = 3;
        $credentials = ['email' => 'test@test.com', 'password' => '12345'];

        $client = new \StdClass;
        $client->secret = 'IRNSHUEC4D3';
        $collection = new Collection([$client]);
        $oauthClient->shouldReceive('where')->once()->with('id', $client_id)->andReturn($collection);

        $data = array_merge([
            'client_id'     => $client_id,
            'client_secret' => 'IRNSHUEC4D3',
            'grant_type'    => 'password',
        ], $credentials);

        $clientHttp->shouldReceive('post')
            ->once()
            ->andThrow(new \Exception('Erro inesperado'));

        $expected = m::mock('Illuminate\Http\JsonResponse[]');
        $response = m::mock('Illuminate\Contracts\Routing\ResponseFactory');
        $response->shouldReceive('json')->once()->andReturn($expected);
        $this->app->instance('Illuminate\Contracts\Routing\ResponseFactory', $response);

        $return = $proxy->attemptLogin($client_id, $credentials);

        $this->assertInstanceOf('Illuminate\Http\JsonResponse', $return);
        $this->assertEquals($expected, $return);
    }

    public function testAttemptLogin()
    {
        $oauthClient = m::mock(OAuthClientEntity::class);
        $clientHttp = m::mock(Client::class);
        $proxy = new OAuthProxy($oauthClient, $clientHttp);

        $client_id = 3;
        $credentials = ['email' => 'test@test.com', 'password' => '12345'];

        $client = new \StdClass;
        $client->secret = 'IRNSHUEC4D3';
        $collection = new Collection([$client]);
        $oauthClient->shouldReceive('where')->once()->with('id', $client_id)->andReturn($collection);

        $data = array_merge([
            'client_id'     => $client_id,
            'client_secret' => 'IRNSHUEC4D3',
            'grant_type'    => 'password',
        ], $credentials);

        $guzzleResponse = new GuzzleResponse(200, ['Accept' => 'application/json']);
        $expected = m::mock('Illuminate\Http\JsonResponse');
        $expected->shouldReceive('setStatusCode')->with(200);
        $expected->shouldReceive('header')->with('Accept', ['application/json']);

        $response = m::mock('Illuminate\Contracts\Routing\ResponseFactory');
        $response->shouldReceive('json')->once()->andReturn($expected);
        $this->app->instance('Illuminate\Contracts\Routing\ResponseFactory', $response);

        $clientHttp->shouldReceive('post')
            ->once()
            ->with('http://localhost/api/oauth/access-token', ['json' => $data])
            ->andReturn($guzzleResponse);

        $return = $proxy->attemptLogin($client_id, $credentials);

        $this->assertEquals($expected, $return);
    }

    public function testAttemptRefresh()
    {
        $oauthClient = m::mock(OAuthClientEntity::class);
        $clientHttp = m::mock(Client::class);
        $proxy = new OAuthProxy($oauthClient, $clientHttp);

        $client_id = 3;
        $credentials = ['rerefresh_token' => 'abcdefghij'];

        $client = new \StdClass;
        $client->secret = 'IRNSHUEC4D3';
        $collection = new Collection([$client]);
        $oauthClient->shouldReceive('where')->once()->with('id', $client_id)->andReturn($collection);

        $data = array_merge([
            'client_id'     => $client_id,
            'client_secret' => 'IRNSHUEC4D3',
            'grant_type'    => 'refresh_token',
        ], $credentials);

        $guzzleResponse = new GuzzleResponse(200, ['Accept' => 'application/json']);
        $expected = m::mock('Illuminate\Http\JsonResponse');
        $expected->shouldReceive('setStatusCode')->with(200);
        $expected->shouldReceive('header')->with('Accept', ['application/json']);

        $response = m::mock('Illuminate\Contracts\Routing\ResponseFactory');
        $response->shouldReceive('json')->once()->andReturn($expected);
        $this->app->instance('Illuminate\Contracts\Routing\ResponseFactory', $response);

        $clientHttp->shouldReceive('post')
            ->once()
            ->with('http://localhost/api/oauth/access-token', ['json' => $data])
            ->andReturn($guzzleResponse);

        $return = $proxy->attemptRefresh($client_id, $credentials);

        $this->assertEquals($expected, $return);
    }
}
