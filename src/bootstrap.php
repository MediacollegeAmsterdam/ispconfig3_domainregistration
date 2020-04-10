<?php

require_once __DIR__ . '/Util/Autoloader.php';

use Domainregistration\Util\Autoloader;

$autoloader = new Autoloader('Domainregistration\\');
spl_autoload_register([$autoloader, 'autoload']);
