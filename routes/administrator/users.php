<?php
use Slim\Http\Request;
use Slim\Http\Response;

// thong tin chi tiet 1 user
$app->get($container['administrator'].'/users', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();
	$shopService = ShopService::getInstance();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('username', $params)) return response(false);

	$user = $userService->getUserByType($params['username'], 'username', true);
	$shop = $shopService->getShopByType($user->id, 'owner_id', false);
	if ($shop) return response(false);
	return response($user);
});

// them hoac chinh sua thong tin user
$app->post($container['administrator'].'/users', function (Request $request, Response $response, array $args) {
	
});

// lay nhieu user
$app->put($container['administrator'].'/users', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('offset', $params)) $params['offset'] = 0;
	if (!array_key_exists('limit', $params)) $params['limit'] = 10;

	$users = $userService->getUsers(null, $params['offset'], $params['limit'], true);

	return response($users);
});