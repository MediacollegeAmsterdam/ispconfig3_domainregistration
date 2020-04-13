<?php

namespace Domainregistration\ISPConfig;

use Domainregistration\ISPConfig\Remoting\RemotingSites;
use Domainregistration\Registrar\Openprovider;
use Domainregistration\Util\Exiter;
use DateTime;
use Exception;
use tform_actions;

final class DomainregistrationEdit extends tform_actions
{
    /**
     * @var Openprovider
     */
    private $registrar;

    /**
     * @var RemotingSites
     */
    private $remoting;

    /**
     * @var Exiter
     */
    private $exiter;

    /**
     * @param Openprovider $registrar
     * @param RemotingSites $remoting
     * @param Exiter $exiter
     * @return void
     */
    public function __construct($registrar, $remoting, $exiter)
    {
        $this->registrar = $registrar;
        $this->remoting = $remoting;
        $this->exiter = $exiter;
    }

    /**
     * Checks availability
     *
     * @return void
     */
    public function onSubmit()
    {
        global $app;

        $this->ensureAvailability($this->dataRecord['domain']);

        if (empty($_POST['confirm'])) {
            $app->tpl->setVar('domain_is_available', true);
            $this->onShow();
            $this->exiter->doExit();
        }

        parent::onSubmit();
    }

    /**
     * @return void
     */
    public function onShowEnd()
    {
        global $app;

        $app->tpl->setVar('domain', $this->dataRecord['domain']);

        parent::onShowEnd();
    }

    /**
     * - Checks availability
     * - Registers the domain and stores the registrar identifier on our side
     * - Creates a domain alias for the user's first web_domain
     * - Creates DNS records for (www.)domain.ext, pointing to the same IP address as the user's first web_domain
     *
     * @param string $sql
     * @return int
     */
    public function onInsertSave($sql)
    {
        global $app;

        // Register domainname
        $this->ensureAvailability($this->dataRecord['domain']);
        $registrarIdentifier = $this->register($this->dataRecord['domain']);

        // Insert database record
        $this->dataRecord['registrar_identifier'] = $registrarIdentifier;
        $this->dataRecord['registered_at'] = (new DateTime())->format('Y-m-d H:i:s');
        $sql = $app->tform->getSQL($this->dataRecord, $app->tform->getCurrentTab(), 'INSERT', $this->id);
        $insertId = parent::onInsertSave($sql);

        // Create alias and insert DNS records upstream
        $this->createDomainAlias($this->dataRecord['domain']);
        $this->createDnsRecords($this->dataRecord['domain']);

        return $insertId;
    }

    /**
     * Only allow creating new domainregistrations if user is within configured limits.
     *
     * @return void
     */
    public function onShowNew()
    {
        global $app, $conf;

        if (empty($conf['domainregistration']['max_active_domains_per_client'])) {
            parent::onShowNew();
            return;
        }

        if ('user' !== $_SESSION['s']['user']['typ']) {
            parent::onShowNew();
            return;
        }

        $sql = 'SELECT * FROM `domainregistration` WHERE `cancelled_at` IS NULL AND `sys_userid` = ?';
        $activeDomains = $app->db->query($sql, $_SESSION['s']['user']['userid']);

        if ($activeDomains->rows() >= $conf['domainregistration']['max_active_domains_per_client']) {
            $app->error($app->tform->wordbook['limit_domainregistration_txt']);
        }

        parent::onShowNew();
    }

    /**
     * This should never be reachable because we don't link here from the listing template, but
     * lets disable the action just in case.
     *
     * @return void
     */
    public function onShowEdit()
    {
        global $app;

        $app->error($app->tform->wordbook['editing_disabled_txt']);
    }

    /**
     * @param string $domain
     * @return void
     */
    private function ensureAvailability($domain)
    {
        global $app;

        if (!$this->registrar->isAvailable($domain)) {
            $app->tpl->setVar('domain_is_taken', true);
            parent::onShow();
            $this->exiter->doExit();
        }
    }

    /**
     * @param string $domain
     * @return int
     */
    private function register($domain)
    {
        return $this->registrar->registerWithComment(
            $domain,
            sprintf('ISPConfig user: %s', $_SESSION['s']['user']['username'])
        );
    }

    /**
     * @param string $domain
     * @return void
     */
    private function createDnsRecords($domain)
    {
        $website = $this->getFirstWebsite();
        if (empty($website)) {
            return;
        }

        $ip = gethostbyname($website['domain']);
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return;
        }

        $this->registrar->dnsAddRecordA($domain, $domain, $ip);
        $this->registrar->dnsAddRecordA($domain, 'www', $ip);
    }

    /**
     * @param string $domain
     * @return void
     */
    private function createDomainAlias($domain)
    {
        $website = $this->getFirstWebsite();
        if (empty($website)) {
            return;
        }

        // Ignore aliasdomain creation errors (eg already exists or exceeding limit)
        try {
            $payload = [
                'type' => 'alias',
                'active' => 'y',
                'subdomain' => 'www',
                'domain' => $domain,
                'server_id' => $website['server_id'],
                'parent_domain_id' => $website['domain_id'],
            ];

            $this->remoting->sites_web_aliasdomain_add(
                null,
                $_SESSION['s']['user']['client_id'],
                $payload
            );
        } catch (Exception $exception) {
        }
    }

    /**
     * @return array
     */
    private function getFirstWebsite()
    {
        $sites = $this->remoting->sites_web_domain_get(
            null,
            [
                'sys_userid' => $_SESSION['s']['user']['userid'],
                'type' => 'vhost',
            ]
        );

        if (empty($sites)) {
            return [];
        }

        return array_shift($sites);
    }
}
