<?php

namespace Domainregistration\Util;

use Domainregistration\Exception\Api\AuthenticationException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use \app;

final class ExceptionHandlerTest extends TestCase
{
    private MockObject $app;
    private MockObject $sentry;
    private ExceptionHandler $subject;

    public function setUp(): void
    {
        $this->app = $this->createMock(app::class);
        $this->sentry = $this->createMock(Sentry::class);

        $this->subject = new ExceptionHandler($this->app, $this->sentry);
    }

    public function testReRaisesUnknownExceptions(): void
    {
        $unknownException = new RuntimeException('foo');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('foo');

        $this->subject->handle($unknownException);
    }

    public function testLogsException(): void
    {
        $exception = new AuthenticationException();

        $this->app
            ->expects($this->once())
            ->method('log');

        $this->subject->handle($exception);
    }

    public function testNotifiesSentry(): void
    {
        $exception = new AuthenticationException();

        $this->sentry
            ->expects($this->once())
            ->method('notify')
            ->with($exception);

        $this->subject->handle($exception);
    }

    public function testTriggersInterfaceError(): void
    {
        $exception = new AuthenticationException();

        $this->app
            ->expects($this->once())
            ->method('error');

        $this->subject->handle($exception);
    }
}
