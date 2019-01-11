<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->put($container['administrator'].'/count', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();
	$shopService = ShopService::getInstance();
	$storeService = StoreService::getInstance();
	$productService = ProductService::getInstance();
	$supplyOrderService = SupplyOrderService::getInstance();
	$supplyOrderService = SupplyOrderService::getInstance();

	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('shop_id', $params)) $params['shop_id'] = '';
	if (!array_key_exists('type', $params)) $params['type'] = 'user';

	switch ($params['type']) {
		case 'user':
			# code...
			break;
		case 'shop':
			# code...
			break;
		case 'store':
			# code...
			break;
		case 'product':
			# code...
			break;
		case 'so':
			# code...
			break;
		case 'do':
			# code...
			break;
		case 'po':
			# code...
			break;
		case 'ads':
			# code...
			break;
		case 'promotion':
			# code...
			break;
		case 'product_group':
			# code...
			break;
		case 'categories':
			# code...
			break;
		case 'redeem':
			# code...
			break;
		default:
			# code...
			break;
	}
	return response(true);
});