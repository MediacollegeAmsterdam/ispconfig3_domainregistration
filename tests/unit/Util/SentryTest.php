<?php

namespace Domainregistration\Util;

use Domainregistration\Exception\AbstractException;
use Domainregistration\Exception\Http\ClientException;
use Domainregistration\Http\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use phpmock\phpunit\PHPMock;

final class SentryTest extends TestCase
{
    use PHPMock;

    private MockObject $client;
    private MockObject $exception;
    private Sentry $subject;

    public function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $this->exception = $this->createMock(AbstractException::class);

        $this->subject = new Sentry('https://123@456.ingest.sentry.io/789', $this->client);
    }

    public function testDoesNothingIfDsnIsEmpty(): void
    {
        $sentry = new Sentry('', $this->client);

        $this->client
            ->expects($this->never())
            ->method('request');

        $sentry->notify($this->exception);
    }

    public function testDoesNothingIfDsnFormatIsInvalid(): void
    {
        $sentry = new Sentry('foo', $this->client);

        $this->client
            ->expects($this->never())
            ->method('request');

        $sentry->notify($this->exception);
    }

    public function testDoesNothingIfPayloadIsEmpty(): void
    {
        $function = $this->getFunctionMock(__NAMESPACE__, 'json_encode');
        $function
            ->expects($this->once())
            ->willReturn(false);

        $this->client
            ->expects($this->never())
            ->method('request');

        $this->subject->notify($this->exception);
    }

    public function testNotifiesSentry(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request');

        $this->subject->notify($this->exception);
    }

    public function testCatchesExceptionAndDoesNothing(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new ClientException());

        $this->subject->notify($this->exception);
    }
}
