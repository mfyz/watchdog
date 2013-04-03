<?php

require_once 'Bootstrap.php';

require_once 'Lib/Slim/Slim.php';
\Slim\Slim::registerAutoloader();
require_once 'LayoutView.php';

$app = new \Slim\Slim(array(
	'view'  => new LayoutView(),
	'debug' => TRUE,
));

require_once 'web_router.php';

$app->run();
