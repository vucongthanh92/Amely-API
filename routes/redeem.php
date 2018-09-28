<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->put($container['prefix'].'/redeem', function (Request $request, Response $response, array $args) {

	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('item_id', $params)) $params['item_id'] = false;
	if (!array_key_exists('owner_id', $params)) $params['owner_id'] = false;
	if (!array_key_exists('quantity_redeem', $params)) $params['quantity_redeem'] = false;


});