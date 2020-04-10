<?php

require_once __DIR__ . '/../../../src/bootstrap.php';

use Domainregistration\Util\Initializer;
use \Exception;

final class remoting_domainregistration extends remoting
{
    /**
     * The argument variables MUST be named with underscore instead of camel case,
     * or ISPConfig's remoting lib won't pass them!
     *
     * @param string $session_id
     * @param int|array $primary_id
     * @return array
     * @throws SoapFault
     */
    public function domainregistration_get($session_id, $primary_id)
    {
        global $app;

        $this->ensureAuthorized($session_id, __FUNCTION__);

        return $this->getDataRecord($primary_id);
    }

    /**
     * The argument variables MUST be named with underscore instead of camel case,
     * or ISPConfig's remoting lib won't pass them!

     * @param string $session_id
     * @param int|array $primary_id
     * @return string|true
     * @throws SoapFault
     */
    public function domainregistration_cancel($session_id, $primary_id)
    {
        global $app, $conf;

        $this->ensureAuthorized($session_id, __FUNCTION__);

        $record = $this->getDataRecord($primary_id);
        $this->ensureHasRegistrarIdentifier($record);
        $this->ensureIsNotCancelled($record);

        $initializer = new Initializer();
        $initializer->initializeSentry($app, $conf);
        $openprovider = $initializer->initializeOpenprovider($app, $conf);

        $response = true;

        try {
            $openprovider->cancel($record['registrar_identifier']);
        } catch (Exception $exception) {
            $response = $exception->getMessage();
        }

        $sql = '
            UPDATE
                domainregistration
            SET
                cancelled_at = NOW()
            WHERE
                id = ?
        ';

        $app->db->query($sql, $record['id']);

        return $response;
    }

    /**
     * @param mixed $record
     * @return void
     * @throws SoapFault
     */
    private function ensureIsNotCancelled($record)
    {
        if (!empty($record['cancelled_at'])) {
            throw new SoapFault(
                'data_processing_error',
                sprintf('Record "%s" was already cancelled at %s.', $record['id'], $record['cancelled_at'])
            );
        }
    }

    /**
     * @param array $record
     * @return void
     * @throws SoapFault
     */
    private function ensureHasRegistrarIdentifier($record)
    {
        if (empty($record['registrar_identifier'])) {
            throw new SoapFault(
                'data_processing_error',
                sprintf('Record "%s" does not have a registrar_identifier.', $record['id'])
            );
        }
    }

    /**
     * @param int|array $primaryId
     * @return void
     */
    private function getDataRecord($primaryId)
    {
        global $app;

        $app->remoting_lib->loadFormDef(__DIR__ . '/../../../form/domainregistration.tform.php');
        $record = $app->remoting_lib->getDataRecord($primaryId, 4);

        if (empty($record)) {
            throw new SoapFault('data_processing_error', sprintf('Record "%s" not found.', print_r($primaryId, true)));
        }

        return $record;
    }

    /**
     * @param mixed $sessionId
     * @param string $methodName
     * @throws SoapFault
     */
    private function ensureAuthorized($sessionId, $methodName)
    {
        if (!$this->checkPerm($sessionId, $methodName)) {
            throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
        }
    }
}
