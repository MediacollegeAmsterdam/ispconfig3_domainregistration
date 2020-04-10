<?php

namespace Domainregistration\Exception;

use Exception;

abstract class AbstractException extends Exception implements ExceptionInterface
{
    const FRIENDLY_MESSAGE = 'Message not set.';

    /**
     * @return string
     */
    public function getFriendlyMessage()
    {
        return static::FRIENDLY_MESSAGE;
    }
}
