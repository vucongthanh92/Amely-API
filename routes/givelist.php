<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->put($container['prefix'].'/givelist', function (Request $request, Response $response, array $args) {
	$itemService = ItemService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	$time = time();
	if (!$params) $params = [];
	if (!array_key_exists('item_id', $params)) $params['item_id'] = false;
	$item = $itemService->getItemByType($params['item_id']);

	
    return response(false);
});