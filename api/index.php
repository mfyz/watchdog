<?php

require_once __DIR__ . '/../Bootstrap.php';

require __DIR__ . '/../Lib/Slim/Slim.php';
\Slim\Slim::registerAutoloader();
require __DIR__ . '/../Lib/JsonView.php';

$app = new \Slim\Slim(array(
	'view' => new JsonView(),
	'debug' => FALSE,
));

$app->watchdog = $watchdog;

require 'helpers.php';
require 'api_router.php';

$app->run();