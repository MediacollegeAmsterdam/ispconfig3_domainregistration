<?php

namespace Domainregistration\Exception\Api;

use Domainregistration\Exception\AbstractException;

final class UnexpectedErrorException extends AbstractException
{
    const FRIENDLY_MESSAGE = 'Unexpected error while communicating with registrar API.';
}
