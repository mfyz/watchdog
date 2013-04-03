<?php

require_once __DIR__ . '/Config/init.php';

require_once ARCHETYPE_DIR . '/error.php';
require_once ARCHETYPE_DIR . '/url.php';
require_once ARCHETYPE_DIR . '/database.php';

$db = db_connect(
	CONFIG_DB_HOST,
	CONFIG_DB_NAME,
	CONFIG_DB_USERNAME,
	CONFIG_DB_PASSWORD,
	(defined('CONFIG_DB_PORT') ? CONFIG_DB_PORT : NULL)
);

if (!$db) {
	throw new Exception('Database connection failed!');
}

require_once __DIR__ . '/Controllers/BaseController.php';

$watchdog = new BaseController($db);