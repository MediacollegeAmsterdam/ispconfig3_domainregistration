<?php

namespace Domainregistration\Registrar\Config;

class OpenproviderConfig implements ConfigInterface
{
    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $ownerHandle;

    /**
     * @var string
     */
    private $adminHandle;

    /**
     * @var string
     */
    private $techHandle;

    /**
     * @var string
     */
    private $billingHandle;

    /**
     * @param string $endpoint
     * @return void
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = rtrim($endpoint, '/');
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * @param string $username
     * @return void
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $password
     * @return void
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $ownerHandle
     * @return void
     */
    public function setOwnerHandle($ownerHandle)
    {
        $this->ownerHandle = $ownerHandle;
    }

    /**
     * @return string
     */
    public function getOwnerHandle()
    {
        return $this->ownerHandle;
    }

    /**
     * @param string $adminHandle
     * @return void
     */
    public function setAdminHandle($adminHandle)
    {
        $this->adminHandle = $adminHandle;
    }

    /**
     * @return string
     */
    public function getAdminHandle()
    {
        return $this->adminHandle;
    }

    /**
     * @param string $techHandle
     * @return void
     */
    public function setTechHandle($techHandle)
    {
        $this->techHandle = $techHandle;
    }

    /**
     * @return string
     */
    public function getTechHandle()
    {
        return $this->techHandle;
    }

    /**
     * @param string $billingHandle
     * @return void
     */
    public function setBillingHandle($billingHandle)
    {
        $this->billingHandle = $billingHandle;
    }

    /**
     * @return string
     */
    public function getBillingHandle()
    {
        return $this->billingHandle;
    }
}
