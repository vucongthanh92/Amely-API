<?php
use Slim\Http\Request;
use Slim\Http\Response;

// thong tin chi tiet 1 user
$app->get($container['administrator'].'/permission', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();
	$shopService = ShopService::getInstance();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('username', $params)) return response(false);

	$user = $userService->getUserByType($params['username'], 'username', true);

	$shop = $shopService->getShopByType($user->id, 'owner_id');
	$user->shop = $shop;
	
	return response($user);
});