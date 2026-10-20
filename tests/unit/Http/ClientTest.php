<?php

namespace Domainregistration\Http;

use Domainregistration\Exception\Http\ClientException;
use PHPUnit\Framework\TestCase;
use phpmock\phpunit\PHPMock;

final class ClientTest extends TestCase
{
    use PHPMock;

    private Client $subject;

    public function setUp(): void
    {
        $this->subject = new Client();
    }

    public function testRequest(): void
    {
        $method = Client::METHOD_GET;
        $url = 'url';
        $headers = ['header'];
        $data = 'data';

        $curlInit = $this->getFunctionMock(__NAMESPACE__, 'curl_init');
        $curlInit
            ->expects($this->once())
            ->willReturn('curl handler');

        $curlSetoptArray = $this->getFunctionMock(__NAMESPACE__, 'curl_setopt_array');
        $curlSetoptArray
            ->expects($this->once())
            ->with('curl handler', [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 60,
            ]);

        $curlExec = $this->getFunctionMock(__NAMESPACE__, 'curl_exec');
        $curlExec
            ->expects($this->once())
            ->with('curl handler')
            ->willReturn('some response');

        $curlError = $this->getFunctionMock(__NAMESPACE__, 'curl_error');
        $this->getFunctionMock(__NAMESPACE__, 'curl_getinfo')->expects($this->once())->willReturn(200);
        $curlError
            ->expects($this->once())
            ->with('curl handler')
            ->willReturn('some curl errors');

        $curlClose = $this->getFunctionMock(__NAMESPACE__, 'curl_close');
        $curlClose
            ->expects($this->once())
            ->willReturn('curl handler');

        $response = $this->subject->request($method, $url, $data, $headers);

        $this->assertEquals('some response', $response);
    }

    public function testRequestFailure(): void
    {
        $this->expectException(ClientException::class);

        $method = Client::METHOD_GET;
        $url = 'url';
        $headers = ['header'];
        $data = 'data';

        $curlInit = $this->getFunctionMock(__NAMESPACE__, 'curl_init');
        $curlInit
            ->expects($this->once())
            ->willReturn('curl handler');

        $curlSetoptArray = $this->getFunctionMock(__NAMESPACE__, 'curl_setopt_array');
        $curlSetoptArray
            ->expects($this->once())
            ->with('curl handler', [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 60,
            ]);

        $curlExec = $this->getFunctionMock(__NAMESPACE__, 'curl_exec');
        $curlExec
            ->expects($this->once())
            ->with('curl handler')
            ->willReturn(false);

        $curlError = $this->getFunctionMock(__NAMESPACE__, 'curl_error');
        $this->getFunctionMock(__NAMESPACE__, 'curl_getinfo')->expects($this->once())->willReturn(0);
        $curlError
            ->expects($this->once())
            ->with('curl handler')
            ->willReturn('some curl errors');

        $curlClose = $this->getFunctionMock(__NAMESPACE__, 'curl_close');
        $curlClose
            ->expects($this->once())
            ->willReturn('curl handler');

        $this->subject->request($method, $url, $data, $headers);
    }

    /** @dataProvider ambiguousStatuses */
    public function testServerErrorsRemainAmbiguousEvenWithJsonErrorBodies(int $status): void
    {
        $this->getFunctionMock(__NAMESPACE__, 'curl_init')->expects($this->once())->willReturn('curl handler');
        $this->getFunctionMock(__NAMESPACE__, 'curl_setopt_array')->expects($this->once())->willReturn(true);
        $this->getFunctionMock(__NAMESPACE__, 'curl_exec')->expects($this->once())
            ->willReturn('{"code":100,"desc":"Internal error"}');
        $this->getFunctionMock(__NAMESPACE__, 'curl_error')->expects($this->once())->willReturn('');
        $this->getFunctionMock(__NAMESPACE__, 'curl_getinfo')->expects($this->once())->willReturn($status);
        $this->getFunctionMock(__NAMESPACE__, 'curl_close')->expects($this->once())->willReturn(null);
        $this->expectException(ClientException::class);
        $this->expectExceptionCode($status);
        $this->subject->request(Client::METHOD_DELETE, '/domains/123', '', []);
    }

    public function ambiguousStatuses(): array
    {
        return [[500], [503], [408]];
    }
}
