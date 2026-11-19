<?php

require_once __DIR__ . '/../../lib/config.inc.php';
require_once __DIR__ . '/../../lib/app.inc.php';
require_once __DIR__ . '/src/bootstrap.php';

use Domainregistration\ISPConfig\RegistrationPolicy;

$app->uses('listform_actions,tpl');
$list_def_file = 'list/domainregistration.list.php';

$app->tpl->setVar('registration_success', false);
$app->tpl->setVar('registration_enabled', RegistrationPolicy::isEnabled($conf));
if (!empty($_GET['registration_success'])) {
    $app->tpl->setVar('registration_success', true);
}

$app->auth->check_module_permissions('domainregistration');
$app->listform_actions->SQLOrderBy = 'ORDER BY domain ASC';
$app->listform_actions->onLoad();
