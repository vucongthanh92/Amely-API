<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->put($container['prefix'].'/likes', function (Request $request, Response $response, array $args) {

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('subject_id', $params)) return response(false);
	if (!array_key_exists('type', $params)) return response(false);

	$type = $params['type'];
	$subject_id = $params['subject_id'];
	if (!in_array($, ['feed', 'business'])) return response(false);

})