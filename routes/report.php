<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->put($container['prefix'].'/report', function (Request $request, Response $response, array $args) {
	$loggedin_user = loggedin_user();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('subject_type', $params))	 	return response(false);
	if (!array_key_exists('subject_id', $params))	 	return response(false);
	if (!array_key_exists('message', $params))	 		return response(false);

	$report_data['owner_id'] = $params['subject_id'];
	$report_data['type'] = $params['subject_type'];
	$report_data['creator_id'] = $loggedin_user->id;
	$report_data['message'] = $params['message'];
	$report_data['status'] = 0;
	return response(ReportService::getInstance()->save($report_data));
});