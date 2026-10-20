<?php

namespace Domainregistration\ISPConfig;

use Domainregistration\Exception\Api\AuthenticationException;
use Domainregistration\Exception\Api\RequestRejectedException;
use Domainregistration\Registrar\Openprovider;
use Exception;
use RuntimeException;

final class DomainCancellation
{
    /** @var Openprovider */
    private $registrar;

    /** @var \db */
    private $db;

    /** @var callable|null */
    private $logger;

    /**
     * @param Openprovider $registrar
     * @param \db $db
     * @param callable|null $logger
     */
    public function __construct($registrar, $db, $logger = null)
    {
        $this->registrar = $registrar;
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * @param array $record
     * @return true|array
     */
    public function cancel($record)
    {
        if (!empty($record['cancelled_at'])) {
            return true;
        }

        // No retry state is stored. Read the registrar on every attempt, including
        // retries after a timeout or a successful DELETE whose response was lost.
        $state = $this->registrarState($record);
        if ($state === 'deleted') {
            return $this->confirm($record);
        }
        if ($state !== 'active') {
            return $this->failure('unknown', 'Could not determine registrar state for ' . $record['domain']);
        }

        try {
            $response = $this->registrar->cancel($record['registrar_identifier']);
            if (!isset($response['code']) || $response['code'] !== 0) {
                throw new RuntimeException('Missing registrar cancellation acknowledgement.');
            }
        } catch (RequestRejectedException $exception) {
            return $this->failure('failed', $exception->getMessage());
        } catch (AuthenticationException $exception) {
            return $this->failure('failed', $exception->getMessage());
        } catch (Exception $exception) {
            return $this->reconcile($record, $exception->getMessage());
        }

        return $this->confirm($record);
    }

    /**
     * @param array $record
     * @return string
     */
    private function registrarState($record)
    {
        foreach ([false, true] as $deletedOnly) {
            try {
                $response = $this->registrar->getInfo($record['registrar_identifier'], $deletedOnly);
                if (isset($response['data']['is_deleted']) && $response['data']['is_deleted'] === true) {
                    return 'deleted';
                }
                $isDeleted = isset($response['data']['is_deleted']) ? $response['data']['is_deleted'] : null;
                $status = isset($response['data']['status']) ? $response['data']['status'] : null;
                if (!$deletedOnly && ($isDeleted === false || $status === 'ACT')) {
                    return 'active';
                }
            } catch (Exception $exception) {
                $this->log('Registrar lookup for ' . $record['domain'] . ': ' . $exception->getMessage());
            }
        }
        // A missing object or failed lookup is not proof of deletion.
        return 'unknown';
    }

    /**
     * @param array $record
     * @param string $error
     * @return true|array
     */
    private function reconcile($record, $error)
    {
        try {
            $response = $this->registrar->getInfo($record['registrar_identifier'], true);
        } catch (Exception $exception) {
            return $this->failure('unknown', $error . '; registrar reconciliation: ' . $exception->getMessage());
        }

        if (isset($response['data']['is_deleted']) && $response['data']['is_deleted'] === true) {
            return $this->confirm($record);
        }
        return $this->failure('unknown', $error . '; deletion not confirmed. A later orphan pass can retry.');
    }

    /**
     * @param string $status
     * @param string $message
     * @return array
     */
    private function failure($status, $message)
    {
        $this->log('Domain cancellation ' . $status . ': ' . $message);
        return ['status' => $status, 'message' => $message];
    }

    /**
     * @param array $record
     * @return true|array
     */
    private function confirm($record)
    {
        try {
            $result = $this->db->query(
                'UPDATE domainregistration SET cancelled_at = COALESCE(cancelled_at, NOW()) WHERE id = ?',
                $record['id']
            );
            if ($result === false) {
                throw new RuntimeException('Could not save cancellation timestamp.');
            }
        } catch (Exception $exception) {
            return $this->failure(
                'unknown',
                'Registrar cancellation confirmed for ' . $record['domain'] .
                ', but its timestamp could not be saved: ' . $exception->getMessage()
            );
        }

        // DNS cleanup is separate from the confirmed cancellation timestamp.
        try {
            $this->registrar->dnsDeleteZone($record['domain']);
        } catch (Exception $exception) {
            $this->log('Domain ' . $record['domain'] . ' cancelled; DNS cleanup failed: ' . $exception->getMessage());
        }
        return true;
    }

    /**
     * @param string $message
     * @return void
     */
    private function log($message)
    {
        if ($this->logger !== null) {
            call_user_func($this->logger, $message);
        }
    }
}
