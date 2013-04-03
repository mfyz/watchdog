<?php

function api_require_params($type = 'POST', $_params = array()){
	if (!is_array($_params)) $_params = array($_params);
	$app = \Slim\Slim::getInstance();

	foreach ($_params as $param) {
		if (
			($type == 'POST' AND !isset($_POST[$param]))
			OR ($type == 'GET' AND !isset($_GET[$param]))
		) {
			$app->render(array(
				'success' => FALSE,
				'error' => 'Missing ' . strtolower($type) . ' parameter: ' . $param
			), 400);
		}
	}
}

function api_require_post_params($_params){
	return api_require_params('POST', $_params);
}

function api_require_get_params($_params){
	return api_require_params('GET', $_params);
}

// Generic error handler
$app->error(function (Exception $e) use ($app) {
	$app->render(array(
		'error' => TRUE,
		'alert' => $e->getMessage()
	), 500);
});

// Not found handler (invalid routes, invalid method types)
$app->notFound(function () use ($app) {
	$app->render(array(
		'error' => TRUE,
		'alert' => 'Invalid route'
	), 404);
});

// Handle Empty response body
$app->hook('slim.after.router', function () use ($app) {
	if (strlen($app->response()->body()) == 0) {
		$app->render(array(
			'error' => TRUE,
			'alert' => 'Empty response'
		), 204);
	}
});
