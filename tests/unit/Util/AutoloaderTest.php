<?php

namespace Domainregistration\Util;

use PHPUnit\Framework\TestCase;
use phpmock\phpunit\PHPMock;

final class AutoloaderTest extends TestCase
{
    use PHPMock;

    private Autoloader $subject;

    public function setUp(): void
    {
        $this->subject = new Autoloader('Foo\\');
    }

    public function testAutoloadReturnsNullIfClassDoesNotExistWithinPrefix(): void
    {
        $returnValue = $this->subject->autoload('Bar\\Baz');

        $this->assertNull($returnValue);
    }

    public function testAutoloadResolvesClassToFile(): void
    {
        $fileExistsMock = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExistsMock
            ->expects($this->once())
            ->with($this->stringContains('src/Util/../Bar.php'));

        $this->subject->autoload('Foo\\Bar');
    }
}
