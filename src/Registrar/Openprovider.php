<?php

namespace Domainregistration\Registrar;

use Domainregistration\Exception\Api\AuthenticationException;
use Domainregistration\Exception\Api\InvalidResponseException;
use Domainregistration\Exception\Api\UnexpectedErrorException;
use Domainregistration\Api\OpenproviderApi;
use Domainregistration\Registrar\Config\OpenproviderConfig;
use Domainregistration\Util\SettingsStore;
use RuntimeException;

class Openprovider implements RegistrarInterface
{
    const SETTINGS_TOKEN_KEY = 'openprovider_bearer_token';

    /**
     * @var OpenproviderConfig
     */
    private $config;

    /**
     * @var OpenproviderApi
     */
    private $api;

    /**
     * @var SettingsStore
     */
    private $settingsStore;

    /**
     * @param OpenproviderConfig $config
     * @param OpenproviderApi $api
     * @param SettingsStore $settingsStore
     */
    public function __construct($config, $api, $settingsStore)
    {
        $this->config = $config;
        $this->settingsStore = $settingsStore;

        $this->api = $api;
        $this->api->setEndpoint($this->config->getEndpoint());
        $this->api->setBearerToken($this->settingsStore->get(self::SETTINGS_TOKEN_KEY));
    }

    /**
     * @return string
     */
    public function refreshBearerToken()
    {
        return $this->api->authLogin($this->config->getUsername(), $this->config->getPassword());
    }

    /**
     * @param string $domain
     * @return bool
     */
    public function isAvailable($domain)
    {
        return $this->callApi('domainCheck', $domain);
    }

    /**
     * @param string $domain
     * @return int
     * @throws InvalidResponseException
     */
    public function register($domain)
    {
        return $this->registerWithComment($domain, '');
    }

    /**
     * @param string $domain
     * @param string $comment
     * @return int
     * @throws InvalidResponseException
     */
    public function registerWithComment($domain, $comment)
    {
        return $this->callApi(
            'domainCreate',
            $domain,
            $this->config->getOwnerHandle(),
            $this->config->getAdminHandle(),
            $this->config->getTechHandle(),
            $this->config->getBillingHandle(),
            $comment
        );
    }

    /**
     * @param string $registrarIdentifier
     * @return bool
     */
    public function cancel($registrarIdentifier)
    {
        return $this->callApi(
            'domainCancel',
            $registrarIdentifier
        );
    }

    /**
     * @param string $domain
     * @param string $fromHostname
     * @param string $toAddress
     * @return bool
     */
    public function dnsAddRecordA($domain, $fromHostname, $toAddress)
    {
        return $this->callApi(
            'dnsAddRecordA',
            $domain,
            $fromHostname,
            $toAddress
        );
    }

    /**
     * @param string $domain
     * @return bool
     * @throws RuntimeException
     * @throws InvalidResponseException
     */
    public function dnsDeleteZone($domain)
    {
        return $this->callApi(
            'dnsDeleteZone',
            $domain
        );
    }

    /**
     * @return mixed
     * @throws RuntimeException
     * @throws InvalidResponseException
     * @throws AuthenticationException
     * @throws UnexpectedErrorException
     */
    public function callApi()
    {
        $args = func_get_args();
        $command = array_shift($args);
        $callback = $this->getApiCallback($command);

        try {
            return call_user_func_array($callback, $args);
        } catch (AuthenticationException $exception) {
            // The bearer token may have expired. Refresh the token and try again.
            $newToken = $this->refreshBearerToken();

            $this->settingsStore->set(self::SETTINGS_TOKEN_KEY, $newToken);
            $this->api->setBearerToken($newToken);

            return call_user_func_array($callback, $args);
        }
    }

    /**
     * @param string $command
     * @return callable
     * @throws RuntimeException
     */
    private function getApiCallback($command)
    {
        $callback = [$this->api, $command];

        if (!is_callable($callback)) {
            throw new RuntimeException(
                sprintf('Critical error: API is not a callback: %s', var_export($callback, true))
            );
        }

        return $callback;
    }
}
