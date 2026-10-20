<?php

namespace Domainregistration\ISPConfig;

use Domainregistration\Exception\Api\RequestRejectedException;
use Domainregistration\Exception\Api\AuthenticationException;
use Domainregistration\Exception\Http\ClientException;
use Domainregistration\Registrar\Openprovider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DomainCancellationTest extends TestCase
{
    private $registrar;
    private $db;
    private $subject;
    private $queries;
    private $logs;
    private $activeResponse;
    private $deletedResponse;
    private $activeLookupError;
    private $deletedLookupError;
    private $record = ['id' => 1, 'domain' => 'example.nl', 'registrar_identifier' => 123];

    public function setUp(): void
    {
        $this->queries = [];
        $this->logs = [];
        $this->activeLookupError = null;
        $this->deletedLookupError = null;
        $this->activeResponse = ['code' => 0, 'data' => ['is_deleted' => false, 'status' => 'ACT']];
        $this->deletedResponse = ['code' => 0, 'data' => ['is_deleted' => false]];
        $this->registrar = $this->createMock(Openprovider::class);
        $this->registrar->method('getInfo')->willReturnCallback(function ($id, $deletedOnly = false) {
            $this->assertSame(123, $id);
            $error = $deletedOnly ? $this->deletedLookupError : $this->activeLookupError;
            if ($error) {
                throw $error;
            }
            return $deletedOnly ? $this->deletedResponse : $this->activeResponse;
        });
        $this->db = $this->createMock(\db::class);
        $this->db->method('query')->willReturnCallback(function ($sql, ...$params) {
            $this->queries[] = [$sql, $params];
            return true;
        });
        $this->subject = new DomainCancellation($this->registrar, $this->db, function ($message) {
            $this->logs[] = $message;
        });
    }

    public function testConfirmedCancellationWritesOnlyExistingTimestampBeforeDnsCleanup(): void
    {
        $this->registrar->method('cancel')->willReturn(['code' => 0]);
        $this->registrar->expects($this->once())->method('dnsDeleteZone')->willReturnCallback(function () {
            $this->assertCount(1, $this->queries);
            $this->assertStringContainsString('cancelled_at = COALESCE', $this->queries[0][0]);
        });
        $this->assertTrue($this->subject->cancel($this->record));
        $this->assertStringNotContainsString('cancellation_status', $this->queries[0][0]);
    }

    public function testDefiniteRejectionDoesNotWriteTimestampOrDeleteDns(): void
    {
        $this->registrar->method('cancel')->willThrowException(new RequestRejectedException('Deletion prohibited', 366));
        $this->registrar->expects($this->never())->method('dnsDeleteZone');
        $this->assertSame('failed', $this->subject->cancel($this->record)['status']);
        $this->assertSame([], $this->queries);
    }

    public function testTimeoutWithConfirmedRemoteDeletionWritesTimestamp(): void
    {
        $this->registrar->method('cancel')->willThrowException(new ClientException('Timeout'));
        $this->deletedResponse = ['code' => 0, 'data' => ['is_deleted' => true]];
        $this->assertTrue($this->subject->cancel($this->record));
        $this->assertCount(1, $this->queries);
    }

    public function testTimeoutWithActiveDomainRemainsUnknown(): void
    {
        $this->registrar->expects($this->once())->method('cancel')->willThrowException(new ClientException('Timeout'));
        $this->registrar->expects($this->never())->method('dnsDeleteZone');
        $this->assertSame('unknown', $this->subject->cancel($this->record)['status']);
        $this->assertSame([], $this->queries);
    }

    public function testHttp500AndFailedReconciliationRemainUnknown(): void
    {
        $this->registrar->method('cancel')->willThrowException(new ClientException('HTTP 500', 500));
        $this->deletedLookupError = new ClientException('HTTP 500', 500);
        $this->assertSame('unknown', $this->subject->cancel($this->record)['status']);
        $this->assertSame([], $this->queries);
    }

    public function testLaterPassConfirmsLostResponseWithoutRepeatingDelete(): void
    {
        $this->registrar->expects($this->once())->method('cancel')->willThrowException(new ClientException('Timeout'));
        $this->assertSame('unknown', $this->subject->cancel($this->record)['status']);
        $this->activeLookupError = new RuntimeException('Not found', 404);
        $this->deletedResponse = ['code' => 0, 'data' => ['is_deleted' => true]];
        $this->assertTrue($this->subject->cancel($this->record));
        $this->assertCount(1, $this->queries);
    }

    public function testLaterPassRetriesActiveDomainAfterUncertainResult(): void
    {
        $attempts = 0;
        $this->registrar->expects($this->exactly(2))->method('cancel')->willReturnCallback(function () use (&$attempts) {
            if (++$attempts === 1) {
                throw new ClientException('Timeout');
            }
            return ['code' => 0];
        });
        $this->assertSame('unknown', $this->subject->cancel($this->record)['status']);
        $this->assertSame([], $this->queries);
        $this->assertTrue($this->subject->cancel($this->record));
        $this->assertCount(1, $this->queries);
    }

    public function testLaterPassRetriesDefiniteRejection(): void
    {
        $attempts = 0;
        $this->registrar->expects($this->exactly(2))->method('cancel')->willReturnCallback(function () use (&$attempts) {
            if (++$attempts === 1) {
                throw new RequestRejectedException('Deletion prohibited', 366);
            }
            return ['code' => 0];
        });
        $this->assertSame('failed', $this->subject->cancel($this->record)['status']);
        $this->assertTrue($this->subject->cancel($this->record));
    }

    public function testDnsFailureDoesNotUndoConfirmedCancellation(): void
    {
        $this->registrar->method('cancel')->willReturn(['code' => 0]);
        $this->registrar->method('dnsDeleteZone')->willThrowException(new RuntimeException('DNS unavailable'));
        $this->assertTrue($this->subject->cancel($this->record));
        $this->assertCount(1, $this->queries);
        $this->assertStringContainsString('DNS cleanup failed', implode(' ', $this->logs));
    }

    public function testAlreadyConfirmedRegistrationDoesNotContactRegistrar(): void
    {
        $this->registrar->expects($this->never())->method('cancel');
        $this->registrar->expects($this->never())->method('getInfo');
        $this->assertTrue($this->subject->cancel($this->record + ['cancelled_at' => '2026-10-18 10:00:00']));
        $this->assertSame([], $this->queries);
    }

    public function testAlreadyDeletedRegistrarObjectNeedsNoDelete(): void
    {
        $this->activeResponse = ['code' => 0, 'data' => ['is_deleted' => true]];
        $this->registrar->expects($this->never())->method('cancel');
        $this->assertTrue($this->subject->cancel($this->record));
    }

    public function testAuthenticationRejectionDoesNotWriteTimestamp(): void
    {
        $this->registrar->method('cancel')->willThrowException(new AuthenticationException('Not authenticated'));
        $this->assertSame('failed', $this->subject->cancel($this->record)['status']);
        $this->assertSame([], $this->queries);
    }

    public function testMissingAcknowledgementRemainsUnknown(): void
    {
        $this->registrar->method('cancel')->willReturn([]);
        $this->assertSame('unknown', $this->subject->cancel($this->record)['status']);
        $this->assertSame([], $this->queries);
    }

    public function testMissingRegistrarObjectDoesNotProveCancellation(): void
    {
        $this->activeLookupError = new RuntimeException('Not found', 404);
        $this->deletedLookupError = new RuntimeException('Not found', 404);
        $this->registrar->expects($this->never())->method('cancel');
        $this->assertSame('unknown', $this->subject->cancel($this->record)['status']);
        $this->assertSame([], $this->queries);
    }

    public function testInconclusiveLookupDoesNotSendDelete(): void
    {
        $this->activeResponse = ['code' => 0, 'data' => []];
        $this->registrar->expects($this->never())->method('cancel');
        $this->assertSame('unknown', $this->subject->cancel($this->record)['status']);
        $this->assertSame([], $this->queries);
    }

    public function testTimestampPersistenceFailureCanBeRecoveredByRegistrarLookup(): void
    {
        $db = $this->createMock(\db::class);
        $db->method('query')->willReturn(false);
        $this->registrar->method('cancel')->willReturn(['code' => 0]);
        $subject = new DomainCancellation($this->registrar, $db, function ($message) {
            $this->logs[] = $message;
        });
        try {
            $result = $subject->cancel($this->record);
        } catch (RuntimeException $exception) {
            $result = ['status' => 'unhandled', 'message' => $exception->getMessage()];
        }
        $this->assertSame('unknown', $result['status']);
        $this->assertStringContainsString('Registrar cancellation confirmed', $result['message']);
        $this->assertStringContainsString('timestamp', implode(' ', $this->logs));
        $this->activeResponse = ['code' => 0, 'data' => ['is_deleted' => true]];
        $this->assertTrue($this->subject->cancel($this->record));
    }

    public function testTimestampDatabaseExceptionIsLoggedAndReturned(): void
    {
        $db = $this->createMock(\db::class);
        $db->method('query')->willThrowException(new RuntimeException('Database unavailable'));
        $this->registrar->method('cancel')->willReturn(['code' => 0]);
        $subject = new DomainCancellation($this->registrar, $db, function ($message) {
            $this->logs[] = $message;
        });
        try {
            $result = $subject->cancel($this->record);
        } catch (RuntimeException $exception) {
            $result = ['status' => 'unhandled', 'message' => $exception->getMessage()];
        }
        $this->assertSame('unknown', $result['status']);
        $this->assertStringContainsString('Database unavailable', $result['message']);
        $this->assertStringContainsString('Database unavailable', implode(' ', $this->logs));
    }
}
