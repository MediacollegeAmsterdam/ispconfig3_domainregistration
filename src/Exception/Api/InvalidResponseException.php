<?php

namespace Domainregistration\Exception\Api;

use Domainregistration\Exception\AbstractException;

final class InvalidResponseException extends AbstractException
{
    const FRIENDLY_MESSAGE = 'Invalid response received from registrar API.';
}
