<?php

namespace Domainregistration\Util;

use Domainregistration\Api\OpenproviderApi;
use Domainregistration\Http\Client;
use Domainregistration\Registrar\Config\OpenproviderConfig;
use Domainregistration\Registrar\Factory\OpenproviderRegistrarFactory;
use Domainregistration\Registrar\Openprovider;
use Domainregistration\Util\ExceptionHandler;
use Domainregistration\Util\Sentry;
use Domainregistration\Util\SettingsStore;

final class Initializer
{
    /**
     * @param \app $app
     * @param array $conf
     * @return void
     */
    public function initializeSentry($app, $conf)
    {
        $client = new Client();
        $sentry = new Sentry($conf['domainregistration']['sentry_dsn'], $client);
        $errorHandler = [new ExceptionHandler($app, $sentry), 'handle'];
        if (is_callable($errorHandler)) {
            set_exception_handler($errorHandler);
        }
    }

    /**
     * @param \app $app
     * @param array $conf
     * @return Openprovider|false
     */
    public function initializeOpenprovider($app, $conf)
    {
        if (empty($conf['domainregistration']['openprovider'])) {
            $app->error('Missing domainregistration configuration. Please see README.md.');
            return false;
        }

        $config = $conf['domainregistration']['openprovider'];

        $openproviderConfig = new OpenproviderConfig();
        $openproviderConfig->setEndpoint($config['endpoint']);
        $openproviderConfig->setUsername($config['username']);
        $openproviderConfig->setPassword($config['password']);
        $openproviderConfig->setOwnerHandle($config['ownerHandle']);
        $openproviderConfig->setAdminHandle($config['adminHandle']);
        $openproviderConfig->setTechHandle($config['techHandle']);
        $openproviderConfig->setBillingHandle($config['billingHandle']);

        $httpClient = new Client();
        $api = new OpenproviderApi($httpClient);
        $settingsStore = new SettingsStore($app->db);

        return OpenproviderRegistrarFactory::create($openproviderConfig, $api, $settingsStore);
    }
}
