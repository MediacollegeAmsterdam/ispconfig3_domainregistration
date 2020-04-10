<?php

namespace Domainregistration\Registrar;

use Domainregistration\Api\OpenproviderApi;
use Domainregistration\Exception\Api\AuthenticationException;
use Domainregistration\Registrar\Config\OpenproviderConfig;
use Domainregistration\Util\SettingsStore;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

final class OpenproviderTest extends TestCase
{
    private MockObject $config;
    private MockObject $api;
    private MockObject $settingsStore;

    public function setUp(): void
    {
        $this->config = $this->createMock(OpenproviderConfig::class);
        $this->api = $this->createMock(OpenproviderApi::class);
        $this->settingsStore = $this->createMock(SettingsStore::class);

        $this->subject = new Openprovider($this->config, $this->api, $this->settingsStore);
    }

    public function testRefreshesBearerToken(): void
    {
        $this->api
            ->expects($this->once())
            ->method('authLogin');

        $this->subject->refreshBearerToken();
    }

    public function testChecksAvailability(): void
    {
        $domain = 'foo.bar';

        $this->api
            ->expects($this->once())
            ->method('domainCheck')
            ->with($domain);

        $this->subject->isAvailable($domain);
    }

    public function testRegistersDomain(): void
    {
        $this->api
            ->expects($this->once())
            ->method('domainCreate');

        $this->subject->register('foo.bar');
    }

    public function testRegistersDomainWithComment(): void
    {
        $this->api
            ->expects($this->once())
            ->method('domainCreate');

        $this->subject->registerWithComment('foo.bar', 'comment');
    }

    public function testCancelsDomain(): void
    {
        $registrarIdentifier = 'ABC-123';

        $this->api
            ->expects($this->once())
            ->method('domainCancel')
            ->with($registrarIdentifier);

        $this->subject->cancel($registrarIdentifier);
    }

    public function testAddsDnsARecord(): void
    {
        $domain = 'foo.bar';
        $fromHostname = 'www.foo.bar';
        $toAddress = '1.3.3.7';

        $this->api
            ->expects($this->once())
            ->method('addDnsRecordA')
            ->with($domain, $fromHostname, $toAddress);

        $this->subject->addDnsRecordA($domain, $fromHostname, $toAddress);
    }

    public function testRefreshesBearerTokenOnAuthenticationException(): void
    {
        $newToken = 's0m3-t0k3n';

        $this->api
            ->expects($this->at(0))
            ->method('domainCreate')
            ->will($this->throwException(new AuthenticationException()));

        $this->api
            ->expects($this->once())
            ->method('authLogin')
            ->willReturn($newToken);

        $this->api
            ->expects($this->once())
            ->method('setBearerToken')
            ->with($newToken);

        $this->settingsStore
            ->expects($this->once())
            ->method('set')
            ->with(Openprovider::SETTINGS_TOKEN_KEY, $newToken);

        $this->subject->register('foo.bar');
    }

    public function testThrowsExceptionIfApiMethodIsNotCallable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Critical error: API is not a callback: /');

        $this->subject->callApi('SomeNoneExistingCommand');
    }
}
