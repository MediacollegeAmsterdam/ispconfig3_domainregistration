<?php

namespace Domainregistration\Registrar\Factory;

use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AbstractRegistrarFactoryTest extends TestCase
{
    public function testThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Must be implemented in concrete class');

        AbstractRegistrarFactory::create('foo', 'bar', 'baz');
    }
}
