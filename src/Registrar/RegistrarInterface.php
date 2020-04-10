<?php

namespace Domainregistration\Registrar;

interface RegistrarInterface
{
    /**
     * @param string $domain
     * @return bool
     */
    public function isAvailable($domain);

    /**
     * @param string $domain
     * @return int
     */
    public function register($domain);

    /**
     * @param string $domain
     * @return bool
     */
    public function cancel($domain);

    /**
     * @param string $domain
     * @param string $fromHostname
     * @param string $toAddress
     * @return bool
     */
    public function addDnsRecordA($domain, $fromHostname, $toAddress);
}
