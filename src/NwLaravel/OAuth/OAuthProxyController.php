<?php

namespace NwLaravel\OAuth;

use Illuminate\Http\Request;

/**
 * Class OAuth Proxy Controller
 *
 * @package NwLaravel\OAuth
 */
trait OAuthProxyController
{

    protected $OAuthProxy;

    abstract protected function getOAuthClientId();

    /**
     * Get OAuthProxy
     *
     * @return OAuthProxy
     */
    protected function getOAuthProxy()
    {
        $this->OAuthProxy = $this->OAuthProxy?: app(OAuthProxy::class);
        return $this->OAuthProxy;
    }

    /**
     * Login Password
     *
     * @param Request $request Request
     *
     * @return Response
     */
    public function token(Request $request)
    {
        return $this->getOAuthProxy()->attemptLogin($this->getOAuthClientId(), $request->all());
    }

    /**
     * Refresh Token
     *
     * @param Request $request Request
     *
     * @return Response
     */
    public function refresh(Request $request)
    {
        return $this->getOAuthProxy()->attemptRefresh($this->getOAuthClientId(), $request->all());
    }
}
