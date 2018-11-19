<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/album', function (Request $request, Response $response, array $args) {
	$feedService = FeedService::getInstance();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('owner_id', $params)) $params['owner_id'] = $loggedin_user->id;
	if (!array_key_exists('offset', $params)) $params['offset'] = 0;
	if (!array_key_exists('limit', $params)) $params['limit'] = 10;

	$loggedin_user = loggedin_user();

	$feed_params[] = [
		'key' => 'poster_id',
		'value' => "= {$params['owner_id']}",
		'operation' => ''
	];
	$feed_params[] = [
		'key' => 'images',
		'value' => "<> ''",
		'operation' => 'AND'
	];
	$feed_params[] = [
		'key' => 'time_created',
		'value' => "DESC",
		'operation' => 'order_by'
	];

	$feeds = $feedService->getFeeds($feed_params, $params['offset'], $params['limit']);

	return response($feeds);
});