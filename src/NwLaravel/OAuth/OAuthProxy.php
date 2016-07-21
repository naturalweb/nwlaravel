<?php

namespace NwLaravel\OAuth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Proxy
 *
 * @package NwLaravel\OAuth
 */
class OAuthProxy
{
    /**
     * @var OAuthClientEntity
     */
    protected $oauthClient;

    /**
     * @var Client
     */
    protected $clientHttp;

    /**
     * @var string
     */
    protected $proxyUrl;

    /**
     * Construct
     *
     * @param OAuthClientEntity $oauthClient OAuth Client Entity
     * @param Client            $clientHttp  Client Http
     */
    public function __construct(OAuthClientEntity $oauthClient, Client $clientHttp)
    {
        $this->oauthClient = $oauthClient;
        $this->clientHttp = $clientHttp;
        $this->proxyUrl = sprintf('%s%s', config('app.url'), config('nwlaravel.oauth.urlToken', '/oauth/access-token'));
    }

    /**
     * Attempt Login Password
     *
     * @param string $client_id   Client Id
     * @param array  $credentials Credentials
     *
     * @return Response
     */
    public function attemptLogin($client_id, array $credentials)
    {
        return $this->proxy('password', $client_id, $credentials);
    }

    /**
     * Attempt Refresh Token
     *
     * @param string $client_id   Client Id
     * @param array  $credentials Credentials
     *
     * @return Response
     */
    public function attemptRefresh($client_id, array $credentials)
    {
        return $this->proxy('refresh_token', $client_id, $credentials);
    }

    /**
     * Proxy
     *
     * @param string $grantType String Grant Type
     * @param string $client_id String Client Id
     * @param array  $data      Array Data
     *
     * @return Response
     */
    private function proxy($grantType, $client_id, array $data = array())
    {
        try {
            $data = array_merge([
                'client_id'     => $client_id,
                'client_secret' => $this->getClientSecret($client_id),
                'grant_type'    => $grantType
            ], $data);

            $guzzleResponse = $this->clientHttp->post($this->proxyUrl, ['json' => $data]);

        } catch (RequestException $e) {
            $body = sprintf('{"error": "%s", "error_description": "%s"}', get_class($e), $e->getMessage());
            $guzzleResponse = $e->hasResponse() ? $e->getResponse() : new GuzzleResponse(500, [], $body);

        } catch (\Exception $e) {
            $body = sprintf('{"error": "%s", "error_description": "%s"}', get_class($e), $e->getMessage());
            $guzzleResponse = new GuzzleResponse(500, [], $body);
        }

        return $this->parseResponse($guzzleResponse);
    }

    /**
     * Get Client Secret
     *
     * @param  string $client_id Client Id
     * @return string
     */
    private function getClientSecret($client_id)
    {
        $client_secret = '';
        $client = $this->oauthClient->where('id', $client_id)->first();
        if ($client) {
            $client_secret = $client->secret;
        }

        return $client_secret;
    }

    /**
     * Parse Response
     *
     * @param ResponseInterface $guzzleResponse
     * @return string
     */
    private function parseResponse(ResponseInterface $guzzleResponse)
    {
        $body = json_decode($guzzleResponse->getBody());
        $response = response()->json($body);
        $response->setStatusCode($guzzleResponse->getStatusCode());

        $headers = $guzzleResponse->getHeaders();
        foreach ($headers as $headerType => $headerValue) {
            $response->header($headerType, $headerValue);
        }

        return $response;
    }
}
