<?php

$app->post('/auto_log', function () use ($app) {
	api_require_post_params(array('message'));

	$trace = input_post('trace');
	$trace = json_decode($trace, TRUE);

	$registered = $app->watchdog->loggerController->log_exception(
		input_post('message'),
		input_post('code'),
		input_post('file'),
		input_post('line'),
		$trace,
		input_post('type')
	);

	$app->render(array(
		'success' => $registered
	), ($registered ? 200 : 500));
});
