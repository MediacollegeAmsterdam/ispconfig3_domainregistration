<?php

require_once __DIR__ . '/../../../src/bootstrap.php';

use Domainregistration\Util\Initializer;
use Domainregistration\ISPConfig\DomainCancellation;

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
        $this->ensureAuthorized($session_id, __FUNCTION__);

        return $this->getDataRecord($primary_id);
    }

    /**
     * The argument variables MUST be named with underscore instead of camel case,
     * or ISPConfig's remoting lib won't pass them!

     * @param string $session_id
     * @param int|array $primary_id
     * @return array|true
     * @throws SoapFault
     */
    public function domainregistration_cancel($session_id, $primary_id)
    {
        global $app, $conf;

        $this->ensureAuthorized($session_id, __FUNCTION__);

        $record = $this->getDataRecord($primary_id);
        $this->ensureHasRegistrarIdentifier($record);

        $initializer = new Initializer();
        $initializer->initializeSentry($app, $conf);
        $openprovider = $initializer->initializeOpenprovider($app, $conf);

        if (!$openprovider) {
            throw new SoapFault('configuration_error', 'Openprovider is not configured.');
        }

        $cancellation = new DomainCancellation($openprovider, $app->db, function ($message) use ($app) {
            $app->log($message, LOGLEVEL_WARN);
        });
        return $cancellation->cancel($record);
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
        $record = $app->remoting_lib->getDataRecord($primaryId);

        if (empty($record)) {
            if (is_array($primaryId) || $primaryId == -1) {
                return [];
            }
            throw new SoapFault('data_processing_error', sprintf('Record "%s" not found.', print_r($primaryId, true)));
        }

        // Add the record owner's username to output if available
        if ($this->hasMultipleResults($record)) {
            foreach ($record as &$item) {
                $item = $this->addUsernameToRecord($item);
            }
        } else {
            $record = $this->addUsernameToRecord($record);
        }

        return $record;
    }

    /**
     * @param array $result
     * @return bool
     */
    private function hasMultipleResults($result)
    {
        $arrayKeys = array_keys($result);
        $firstArraykey = array_shift($arrayKeys);

        return is_numeric($firstArraykey);
    }

    /**
     * @param array $record
     * @return array
     */
    private function addUsernameToRecord($record)
    {
        global $app;

        if (empty($record['sys_groupid'])) {
            return $record;
        }

        $sql = '
            SELECT username
            FROM client
            LEFT JOIN sys_group ON sys_group.client_id = client.client_id
            WHERE sys_group.groupid = ?
        ';

        $user = $app->db->queryOneRecord(
            $sql,
            $record['sys_groupid']
        );

        $record['username'] = '';
        if (!empty($user['username'])) {
            $record['username'] = $user['username'];
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
