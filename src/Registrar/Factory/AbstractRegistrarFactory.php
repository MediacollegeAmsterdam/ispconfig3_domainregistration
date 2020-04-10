<?php

namespace Domainregistration\Registrar\Factory;

use Domainregistration\Api\ApiInterface;
use Domainregistration\Registrar\Config\ConfigInterface;
use Domainregistration\Registrar\RegistrarInterface;
use Domainregistration\Util\SettingsStore;
use RuntimeException;

abstract class AbstractRegistrarFactory
{
    /**
     * @param ConfigInterface $config
     * @param ApiInterface $api
     * @param SettingsStore $settingsStore
     * @return RegistrarInterface
     * @throws RuntimeException
     */
    public static function create($config, $api, $settingsStore)
    {
        throw new RuntimeException('Must be implemented in concrete class');
    }
}
