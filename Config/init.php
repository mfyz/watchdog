<?php

$_config_route = array(
	'local' => array(
		'watchdog.loc',
	)
);

$environment = NULL;
foreach ($_config_route as $env_id => $_hosts) {
	if (in_array($_SERVER['HTTP_HOST'], $_hosts)) {
		$environment = $env_id;
		break;
	}
}

if (!$environment) die('Configuration Init Error');

require __DIR__ . '/' . $environment . '.php';