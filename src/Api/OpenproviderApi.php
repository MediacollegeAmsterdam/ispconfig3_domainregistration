<?php

namespace Domainregistration\Api;

use Domainregistration\Exception\Api\AuthenticationException;
use Domainregistration\Exception\Api\InvalidResponseException;
use Domainregistration\Exception\Api\UnexpectedErrorException;
use Domainregistration\Http\Client;
use RuntimeException;

class OpenproviderApi implements ApiInterface
{
    const DOMAIN_CHECK_FREE = 'free';
    const DOMAIN_CREATE_ACCEPT_EAP_FEE = 0;
    const DOMAIN_CREATE_ACCEPT_PREMIUM_FEE = 0;
    const DOMAIN_CREATE_AUTORENEW = 'on';
    const DOMAIN_CREATE_PERIOD = 1;
    const DOMAIN_CREATE_IS_DNSSEC_ENABLE = true;
    const DOMAIN_CREATE_NS_GROUP = 'dns-openprovider';
    const DNS_ZONE_TYPE_A = 'A';
    const DNS_ZONE_TTL = 3600;

    /**
     * @var Client
     */
    protected $httpClient;

    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var string|null
     */
    private $bearerToken;

    /**
     * @param Client $httpClient
     */
    public function __construct($httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param string $endpoint
     * @return void
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
    }

    /**
     * @param string|null $bearerToken
     * @return void
     */
    public function setBearerToken($bearerToken)
    {
        $this->bearerToken = $bearerToken;
    }

    /**
     * @param string $username
     * @param string $password
     * @return string
     * @throws RuntimeException
     * @throws InvalidResponseException
     * @throws AuthenticationException
     * @throws UnexpectedErrorException
     */
    public function authLogin($username, $password)
    {
        $url = sprintf('%s/auth/login', $this->endpoint);
        $payload = [
            'username' => $username,
            'password' => $password,
        ];

        $response = $this->call(Client::METHOD_POST, $url, $payload);

        if (empty($response['data']['token'])) {
            throw new InvalidResponseException(
                sprintf('Missing token in response: %s', var_export($response, true))
            );
        }

        return $response['data']['token'];
    }

    /**
     * @param string $domain
     * @return bool
     * @throws RuntimeException
     * @throws InvalidResponseException
     * @throws AuthenticationException
     * @throws UnexpectedErrorException
     */
    public function domainCheck($domain)
    {
        $parts = explode('.', $domain, 2);
        $url = sprintf('%s/domains/check', $this->endpoint);
        $payload = [
            'domains' => [
                [
                    'name' => $parts[0],
                    'extension' => $parts[1],
                ],
            ]
        ];

        $response = $this->callAuthenticated(Client::METHOD_POST, $url, $payload);

        if (empty($response['data']['results'][0]['status'])) {
            throw new InvalidResponseException(
                sprintf('Missing domain check status in response: %s', var_export($response, true))
            );
        }

        return self::DOMAIN_CHECK_FREE === $response['data']['results'][0]['status'];
    }

    /**
     * @param string $domain
     * @param string $ownerHandle
     * @param string $adminHandle
     * @param string $techHandle
     * @param string $billingHandle
     * @param string $comment
     * @return int
     * @throws AuthenticationException
     * @throws RuntimeException
     * @throws InvalidResponseException
     * @throws UnexpectedErrorException
     */
    public function domainCreate(
        $domain,
        $ownerHandle,
        $adminHandle,
        $techHandle,
        $billingHandle,
        $comment
    ) {
        $parts = explode('.', $domain, 2);
        $url = sprintf('%s/domains', $this->endpoint);
        $payload = [
            'accept_eap_fee' => self::DOMAIN_CREATE_ACCEPT_EAP_FEE,
            'accept_premium_fee' => self::DOMAIN_CREATE_ACCEPT_PREMIUM_FEE,
            'autorenew' => self::DOMAIN_CREATE_AUTORENEW,
            'period' => self::DOMAIN_CREATE_PERIOD,
            'is_dnssec_enabled' => self::DOMAIN_CREATE_IS_DNSSEC_ENABLE,
            'ns_group' => self::DOMAIN_CREATE_NS_GROUP,
            'owner_handle' => $ownerHandle,
            'admin_handle' => $adminHandle,
            'tech_handle' => $techHandle,
            'billing_handle' => $billingHandle,
            'comments' => $comment,
            'domain' => [
                'name' => $parts[0],
                'extension' => $parts[1],
            ],
        ];

        $response = $this->callAuthenticated(Client::METHOD_POST, $url, $payload);

        if (empty($response['data']['id'])) {
            throw new InvalidResponseException(
                sprintf('Error while registering domainname. Response: %s', var_export($response, true))
            );
        }

        return $response['data']['id'];
    }

    /**
     * @param string $registrarIdentifier
     * @return array
     */
    public function domainCancel($registrarIdentifier)
    {
        $url = sprintf('%s/domains/%s', $this->endpoint, $registrarIdentifier);

        return $this->callAuthenticated(Client::METHOD_DELETE, $url);
    }

    /**
     * @param string $domain
     * @param string $fromHostname
     * @param string $toAddress
     * @return bool
     */
    public function addDnsRecordA($domain, $fromHostname, $toAddress)
    {
        $url = sprintf('%s/dns/zones/%s', $this->endpoint, $domain);
        $payload = [
            'records' => [
                'add' => [
                    [
                        'name' => $domain === $fromHostname ? '' : $fromHostname,
                        'value' => $toAddress,
                        'type' => self::DNS_ZONE_TYPE_A,
                        'ttl' => self::DNS_ZONE_TTL,
                    ]
                ],
            ],
        ];

        $response = $this->callAuthenticated(Client::METHOD_PUT, $url, $payload);

        if (empty($response['data']['success'])) {
            throw new InvalidResponseException(
                sprintf('Error while adding DNS A record. Response: %s', var_export($response, true))
            );
        }

        return $response['data']['success'];
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $payload
     * @param array $headers
     * @return array
     * @throws RuntimeException
     * @throws InvalidResponseException
     * @throws AuthenticationException
     * @throws UnexpectedErrorException
     */
    private function call($method, $url, $payload = [], $headers = [])
    {
        $encodedPayload = json_encode($payload);
        if (false === $encodedPayload) {
            throw new RuntimeException(sprintf('Could not JSON encode payload: %s', var_export($payload, true)));
        }

        $rawResponse = $this->httpClient->request($method, $url, $encodedPayload, $headers);
        $decodedResponse = json_decode($rawResponse, true);

        $this->ensureValidResponse($rawResponse, $decodedResponse);
        $this->ensureSuccessfulAuthentication($rawResponse, $decodedResponse);
        $this->ensureNoErrors($rawResponse, $decodedResponse);

        return $decodedResponse;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $payload
     * @return array
     * @throws AuthenticationException
     * @throws RuntimeException
     * @throws InvalidResponseException
     * @throws UnexpectedErrorException
     */
    private function callAuthenticated($method, $url, $payload = [])
    {
        $this->ensureBearerTokenIsSet();

        return $this->call($method, $url, $payload, [$this->getAuthenticationHeader()]);
    }

    /**
     * @return void
     * @throws AuthenticationException
     */
    private function ensureBearerTokenIsSet()
    {
        if (!empty($this->bearerToken)) {
            return;
        }

        throw new AuthenticationException('Bearer token is not set');
    }

    /**
     * @param string|null $rawResponse
     * @param array|null $decodedResponse
     * @return void
     * @throws InvalidResponseException
     */
    private function ensureValidResponse($rawResponse, $decodedResponse)
    {
        if (null !== $decodedResponse) {
            return;
        }

        throw new InvalidResponseException(sprintf('Raw response: %s', var_export($rawResponse, true)));
    }

    /**
     * @see https://support.openprovider.eu/hc/en-us/articles/216644928-API-Error-Codes
     * @param string $rawResponse
     * @param array $decodedResponse
     * @return void
     * @throws AuthenticationException
     */
    private function ensureSuccessfulAuthentication($rawResponse, $decodedResponse)
    {
        if (empty($decodedResponse['code']) || 196 !== $decodedResponse['code']) {
            return;
        }

        throw new AuthenticationException(sprintf('Raw response: %s', var_export($rawResponse, true)));
    }

    /**
     * @see https://support.openprovider.eu/hc/en-us/articles/216644928-API-Error-Codes
     * @param string $rawResponse
     * @param array $decodedResponse
     * @return void
     * @throws UnexpectedErrorException
     */
    private function ensureNoErrors($rawResponse, $decodedResponse)
    {
        if (array_key_exists('code', $decodedResponse) && 0 === $decodedResponse['code']) {
            return;
        }

        throw new UnexpectedErrorException(sprintf('Raw response: %s', var_export($rawResponse, true)));
    }

    /**
     * @return string
     */
    private function getAuthenticationHeader()
    {
        return sprintf('Authorization: Bearer %s', $this->bearerToken);
    }
}
