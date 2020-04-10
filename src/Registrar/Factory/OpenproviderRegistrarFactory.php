<?php

namespace Domainregistration\Registrar\Factory;

use Domainregistration\Api\OpenproviderApi;
use Domainregistration\Registrar\Config\OpenproviderConfig;
use Domainregistration\Registrar\Openprovider;
use Domainregistration\Util\SettingsStore;

final class OpenproviderRegistrarFactory extends AbstractRegistrarFactory
{
    /**
     * @param OpenproviderConfig $config
     * @param OpenproviderApi $api
     * @param SettingsStore $settingsStore
     * @return Openprovider
     */
    public static function create($config, $api, $settingsStore)
    {
        return new Openprovider($config, $api, $settingsStore);
    }
}
