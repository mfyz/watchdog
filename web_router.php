<?php

$app->get('/', function () use ($app) {
	$app->render('Home');
});

$app->get('/issues', function () use ($app) {
	$app->render('IssueList');
});

$app->get('/issue/:issue_id', function () use ($app) {
	$app->render('IssueDetails');
});

$app->get('/api_details', function () use ($app) {
	$app->render('ApiDetails');
});