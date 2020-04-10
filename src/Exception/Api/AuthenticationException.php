<?php

namespace Domainregistration\Exception\Api;

use Domainregistration\Exception\AbstractException;

final class AuthenticationException extends AbstractException
{
    const FRIENDLY_MESSAGE = 'Authentication with the registrar API failed.';
}
