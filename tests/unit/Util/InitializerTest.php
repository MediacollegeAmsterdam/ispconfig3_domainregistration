<?php

namespace Domainregistration\Util;

use Domainregistration\Registrar\Openprovider;
use PHPUnit\Framework\TestCase;
use phpmock\phpunit\PHPMock;

final class InitializerTest extends TestCase
{
    use PHPMock;

    private const VALID_CONFIGURATION = [
        'domainregistration' => [
            'sentry_dsn' => 'https://sentry',
            'openprovider' => [
                'endpoint' => 'https://openprovider',
                'username' => 'y',
                'password' => 'x',
                'ownerHandle' => 'YN000694-NL',
                'adminHandle' => 'YN000694-NL',
                'techHandle' => 'YN000694-NL',
                'billingHandle' => 'YN000694-NL',
            ],
        ],
    ];

    private Initializer $subject;

    public function setUp(): void
    {
        $this->subject = new Initializer();
    }

    public function testCreatesOpenproviderRegistrar(): void
    {
        $registrar = $this->subject->initializeOpenprovider(new \app(), self::VALID_CONFIGURATION);

        $this->assertInstanceOf(Openprovider::class, $registrar);
    }

    public function testReturnsFalseIfConfigurationIsMissing(): void
    {
        $registrar = $this->subject->initializeOpenprovider(new \app(), []);

        $this->assertFalse($registrar);
    }

    public function testShowsErrorIfConfigurationIsMissing(): void
    {
        $app = $this->createMock(\app::class);
        $app->expects($this->once())
            ->method('error')
            ->with('Missing domainregistration configuration. Please see README.md.');

        $this->subject->initializeOpenprovider($app, []);
    }

    public function testInitializesSentry(): void
    {
        $app = $this->createMock(\app::class);

        $function = $this->getFunctionMock(__NAMESPACE__, 'set_exception_handler');
        $function->expects($this->once());

        $this->subject->initializeSentry($app, self::VALID_CONFIGURATION);
    }
}
