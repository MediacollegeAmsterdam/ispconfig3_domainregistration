<?php

namespace Domainregistration\Api;

use Domainregistration\Exception\Api\AuthenticationException;
use Domainregistration\Exception\Api\InvalidResponseException;
use Domainregistration\Exception\Api\UnexpectedErrorException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Domainregistration\Http\Client;
use RuntimeException;

final class OpenproviderApiTest extends TestCase
{
    private MockObject $client;
    private OpenproviderApi $subject;

    public function setUp(): void
    {
        $this->client = $this->createMock(Client::class);

        $this->subject = new OpenproviderApi($this->client);
        $this->subject->setBearerToken('t0k3n');
    }

    public function testAuthLoginSuccess(): void
    {
        $expectedToken = 'foo';
        $response = [
            'code' => 0,
            'data' => [
                'token' => $expectedToken,
            ],
        ];

        $this->client
            ->expects($this->once())
            ->method('request')
            ->with(Client::METHOD_POST, '/auth/login', '{"username":"user","password":"pass"}')
            ->willReturn(json_encode($response));

        $token = $this->subject->authLogin('user', 'pass');

        $this->assertEquals($expectedToken, $token);
    }

    public function testChecksFreeDomain(): void
    {
        $response = [
            'code' => 0,
            'data' => [
                'results' => [
                    0 => [
                        'status' => OpenproviderApi::DOMAIN_CHECK_FREE,
                    ],
                ],
            ],
        ];

        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturn(json_encode($response));

        $result = $this->subject->domainCheck('foo.com');

        $this->assertTrue($result);
    }

    public function testChecksTakenDomain(): void
    {
        $response = [
            'code' => 0,
            'data' => [
                'results' => [
                    0 => [
                        'status' => 'unavailable',
                    ],
                ],
            ],
        ];

        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturn(json_encode($response));

        $result = $this->subject->domainCheck('bar.com');

        $this->assertFalse($result);
    }

    public function testThrowsExceptionOnMissingDomainCheckResult(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessageMatches('/Missing domain check status in response: /');

        $response = [
            'code' => 0,
            'data' => [],
        ];

        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturn(json_encode($response));

        $this->subject->domainCheck('bar.com');
    }

    public function testCreatesDomain(): void
    {
        $response = [
            'code' => 0,
            'data' => [
                'id' => 1337,
            ],
        ];

        $this->client
            ->expects($this->once())
            ->method('request')
            ->with(Client::METHOD_POST, '/domains')
            ->willReturn(json_encode($response));

        $registrarIdentifier = $this->subject->domainCreate(
            'bar.com',
            'owner',
            'admin',
            'tech',
            'billing',
            'comment'
        );

        $this->assertEquals(1337, $registrarIdentifier);
    }

    public function testThrowsExceptionIfDomainCreationDoesNotReturnRegistrarIdentifier(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessageMatches('/^Error while registering domainname. Response: /');

        $response = [
            'code' => 0,
            'data' => [],
        ];

        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturn(json_encode($response));

        $this->subject->domainCreate(
            'bar.com',
            'owner',
            'admin',
            'tech',
            'billing',
            'comment'
        );
    }

    public function testCancelsDomain(): void
    {
        $response = [
            'code' => 0,
        ];

        $this->client
            ->expects($this->once())
            ->method('request')
            ->with(Client::METHOD_DELETE, '/domains/123-ABC')
            ->willReturn(json_encode($response));

        $result = $this->subject->domainCancel('123-ABC');

        $this->assertEquals($response, $result);
    }

    public function testGetsDomainInfo(): void
    {
        $response = [
            'code' => 0,
        ];

        $this->client
            ->expects($this->once())
            ->method('request')
            ->with(Client::METHOD_GET, '/domains/123-ABC')
            ->willReturn(json_encode($response));

        $result = $this->subject->domainGetInfo('123-ABC');

        $this->assertEquals($response, $result);
    }

    public function testAddsDnsARecord(): void
    {
        $response = [
            'code' => 0,
            'data' => [
                'success' => true
            ],
        ];

        $this->client
            ->expects($this->once())
            ->method('request')
            ->with(Client::METHOD_PUT, '/dns/zones/bar.com')
            ->willReturn(json_encode($response));

        $result = $this->subject->dnsAddRecordA('bar.com', 'hostname', '1.2.3.4');

        $this->assertTrue($result);
    }

    public function testDeletesDnsZone(): void
    {
        $response = [
            'code' => 0,
            'data' => [
                'success' => true
            ],
        ];

        $this->client
            ->expects($this->once())
            ->method('request')
            ->with(Client::METHOD_DELETE, '/dns/zones/foo.bar')
            ->willReturn(json_encode($response));

        $result = $this->subject->dnsDeleteZone('foo.bar');

        $this->assertTrue($result);
    }

    public function testThrowsExceptionIfDnsZoneCouldNotBeDeleted(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessageMatches('/^Error while deleting DNS zone. Response: /');

        $response = [
            'code' => 0,
            'data' => [
                'success' => false
            ],
        ];

        $this->client
            ->expects($this->once())
            ->method('request')
            ->with(Client::METHOD_DELETE, '/dns/zones/bar.com')
            ->willReturn(json_encode($response));

        $this->subject->dnsDeleteZone('bar.com');
    }

    public function testThrowsExceptionIfDnsRecordCouldNotBeAdded(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessageMatches('/^Error while adding DNS A record. Response: /');

        $response = [
            'code' => 0,
            'data' => [
                'success' => false
            ],
        ];

        $this->client
            ->expects($this->once())
            ->method('request')
            ->with(Client::METHOD_PUT, '/dns/zones/bar.com')
            ->willReturn(json_encode($response));

        $this->subject->dnsAddRecordA('bar.com', 'hostname', '1.2.3.4');
    }

    public function testThrowsExceptionIfAuthLoginTokenIsMissing(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessageMatches('/^Missing token in response: /');

        $response = [
            'code' => 0,
        ];

        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturn(json_encode($response));

        $this->subject->authLogin('user', 'pass');
    }

    public function testThrowsAuthenticationException(): void
    {
        $this->expectException(AuthenticationException::class);

        $response = [
            'code' => 196,
        ];

        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturn(json_encode($response));

        $this->subject->authLogin('user', 'pass');
    }

    public function testThrowsUnexpectedErrorExceptionIfMissingCodeInResponse(): void
    {
        $this->expectException(UnexpectedErrorException::class);

        $response = [
        ];

        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturn(json_encode($response));

        $this->subject->authLogin('user', 'pass');
    }

    public function testThrowsUnexpectedErrorExceptionIfCodeIsNotZero(): void
    {
        $this->expectException(UnexpectedErrorException::class);

        $response = [
            'code' => 1,
        ];

        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturn(json_encode($response));

        $this->subject->authLogin('user', 'pass');
    }

    public function testThrowsExceptionOnInvalidPayload(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Could not JSON encode payload: /');
        $this->subject->authLogin(NAN, NAN);
    }

    public function testThrowsExceptionOnInvalidResponse(): void
    {
        $response = '{';

        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessageMatches('/Raw response: /');
        $this->subject->authLogin('user', 'pass');
    }

    public function testThrowsExceptionIfBearerTokenIsNotSet(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Bearer token is not set');

        $this->subject->setBearerToken(null);

        $this->subject->domainCheck('foo.com');
    }
}
