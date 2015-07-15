<?php

use Nette\Application\UI\Form;

require __DIR__ . '/../../../vendor/autoload.php';

$configurator = new Nette\Configurator;

//$configurator->setDebugMode(false);
//Tracy\Debugger::enable(Tracy\Debugger::PRODUCTION);

$configurator->enableDebugger(__DIR__ . '/../log');

$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
	->addDirectory(__DIR__)
    ->addDirectory(__DIR__ . '/../libs')
	->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

$container = $configurator->createContainer();

return $container;
