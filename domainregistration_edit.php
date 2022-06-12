<?php

require_once __DIR__ . '/../../lib/config.inc.php';
require_once __DIR__ . '/../../lib/app.inc.php';
require_once __DIR__ . '/../../lib/classes/remoting.inc.php';
require_once __DIR__ . '/../../lib/classes/remote.d/sites.inc.php';
require_once __DIR__ . '/src/bootstrap.php';

use Domainregistration\ISPConfig\DomainregistrationEdit;
use Domainregistration\ISPConfig\Remoting\RemotingSites;
use Domainregistration\Util\Exiter;
use Domainregistration\Util\Initializer;

$app->uses('tpl,tform,tform_actions');
$tform_def_file = 'form/domainregistration.tform.php';
$app->auth->check_module_permissions('domainregistration');

$app->tpl->setVar('warning_txt', null);
if (!empty($conf['domainregistration']['warning_txt'])) {
    $app->tpl->setVar('warning_txt', $conf['domainregistration']['warning_txt']);
}

$initializer = new Initializer();
$initializer->initializeSentry($app, $conf);
$openprovider = $initializer->initializeOpenprovider($app, $conf);
$remoting = new RemotingSites();

$exiter = new Exiter();
$page = new DomainregistrationEdit($openprovider, $remoting, $exiter);
$page->onLoad();
