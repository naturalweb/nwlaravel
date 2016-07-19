<?php

namespace Tests\OAuth;

use Tests\TestCase;
use Mockery as m;
use NwLaravel\OAuth\OAuthProxy;
use NwLaravel\OAuth\OAuthProxyController;
use Illuminate\Http\Request;

class OAuthProxyControllerTest extends TestCase
{
    public function testActionToken()
    {
        $credentials = ['email' => 'user', 'password' => '123'];
        $proxy = m::mock(OAuthProxy::class);
        $proxy->shouldReceive('attemptLogin')
            ->once()
            ->with('APP-NWLARAVEL-TEST', $credentials)
            ->andReturn('ResponseJson');

        $request = new Request($credentials);
        $controller = new StumbProxyController($proxy);
        
        $this->assertEquals('ResponseJson', $controller->token($request));
    }

    public function testActionRefreshToken()
    {
        $credentials = ['refresh_token' => 'wxyz'];
        $proxy = m::mock(OAuthProxy::class);
        $proxy->shouldReceive('attemptRefresh')
            ->once()
            ->with('APP-NWLARAVEL-TEST', $credentials)
            ->andReturn('ResponseJson');
        $this->app->instance(OAuthProxy::class, $proxy);

        $request = new Request($credentials);
        $controller = new StumbProxyController;
        
        $this->assertEquals('ResponseJson', $controller->refresh($request));
    }
}

/**
* STUB-CONTROLLER
*/
class StumbProxyController
{
    use OAuthProxyController;

    /**
     * Construct
     *
     * @param Proxy $OAuthProxy
     */
    public function __construct(OAuthProxy $OAuthProxy = null)
    {
        $this->OAuthProxy = $OAuthProxy;
    }

    protected function getOAuthClientId()
    {
        return 'APP-NWLARAVEL-TEST';
    }
}
