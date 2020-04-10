<?php

namespace Domainregistration\Exception\Http;

use Domainregistration\Exception\AbstractException;

final class ClientException extends AbstractException
{
    const FRIENDLY_MESSAGE = 'HTTP error while communicating with registrar API.';
}
