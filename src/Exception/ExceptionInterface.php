<?php

namespace Domainregistration\Exception;

interface ExceptionInterface
{
    /**
     * @return string
     */
    public function getFriendlyMessage();
}
