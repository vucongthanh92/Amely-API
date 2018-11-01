<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->put($container['prefix'].'/wishlist', function (Request $request, Response $response, array $args) {
	$itemService = ItemService::getInstance();
	$relationshipService = RelationshipService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('item_id', $params)) $params['item_id'] = false;
	$item = $itemService->getItemByType($params['item_id']);
	if (!$item) return response(false);
	if ($item->status != 1) return response(false);
	if ($item->wishlist == 1) return response(true);
	$item = object_cast("Item", $item);
	$item->data->wishlist = 1;
	$item->where = "id = {$item->id}";
	return response($item->update());
});

$app->delete($container['prefix'].'/wishlist', function (Request $request, Response $response, array $args) {
	$itemService = ItemService::getInstance();
	$relationshipService = RelationshipService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('item_id', $params)) $params['item_id'] = false;
	$item = $itemService->getItemByType($params['item_id']);
	if (!$item) return response(false);
	if ($item->status != 1) return response(false);
	if ($item->wishlist != 1) return response(false);
	$item = object_cast("Item", $item);
	$item->data->wishlist = 0;
	$item->where = "id = {$item->id}";
	return response($item->update());
});
