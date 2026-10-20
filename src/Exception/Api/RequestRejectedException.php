<?php

namespace Domainregistration\Exception\Api;

use Domainregistration\Exception\AbstractException;

final class RequestRejectedException extends AbstractException
{
    const FRIENDLY_MESSAGE = 'The registrar rejected the request.';
}
